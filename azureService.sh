#!/bin/bash

set -e  # Stop the script if an error occurs

log() {
    echo "$(date +'%Y-%m-%d %H:%M:%S') - $1"
}

get_access_token() {
    local url="$1"
    local client_id="$2"
    local client_secret="$3"
    local scope="$4"

    local response=$(curl -s -X POST "$url" \
        -d "grant_type=client_credentials" \
        -d "client_id=$client_id" \
        -d "client_secret=$client_secret" \
        -d "scope=$scope")

    echo "$response" | jq -r '.access_token'
}

get_app_config() {
    local access_token="$1"
    local project_name="$2"
    local app_config="$3"

    local response=$(curl -s -X GET "$app_config/kv?key=$project_name*" \
        -H "Authorization: Bearer $access_token" \
        -H "Content-Type: application/json")

    echo "$response"
}

get_secrets_from_keyvault() {
    local vault_name="$1"
    local access_token="$2"
    local all_secrets="[]"

    local next_url="${vault_name}secrets?api-version=7.4"

    # Iterate while there is a nextLink
    while [[ -n "$next_url" ]]; do
        local response=$(curl -s -X GET "$next_url" \
            -H "Authorization: Bearer $access_token" \
            -H "Content-Type: application/json")

        # VALIDATION 1: Check that response is not empty
        if [[ -z "$response" ]]; then
            log "ERROR: Empty response from Key Vault at URL: $next_url"
            return 1
        fi

        # VALIDATION 2: Verify that it is valid JSON
        if ! echo "$response" | jq empty 2>/dev/null; then
            log "ERROR: The response is not valid JSON:"
            log "$response"
            return 1
        fi

        #  VALIDATION 3: Verify that it has the expected structure (.value)
        if ! echo "$response" | jq -e '.value' >/dev/null 2>&1; then
            log "ERROR: The response does not contain the ‘.value’ field:"
            log "$response"
            return 1
        fi

        # Extract the data
        local page_secrets=$(echo "$response" | jq -r '.value')

        # VALIDATION 4: Verify that `page_secrets` is a valid array
        if ! echo "$page_secrets" | jq empty 2>/dev/null; then
            log "ERROR: .value does not contain valid JSON"
            return 1
        fi

        # Combine with the previous secrets (now for sure)
        all_secrets=$(echo "$all_secrets" | jq --argjson new "$page_secrets" '. + $new')

        # Get the nextLink for the next iteration (it's already provided as a full URL)
        next_url=$(echo "$response" | jq -r '.nextLink // empty')

    done

    # Generate the final response in the expected format
    echo "{\"value\": $all_secrets}"
}

get_value_secret_from_keyvault() {
    local secret_url="$1"
    local access_token="$2"

    local response=$(curl -s -X GET "$secret_url?api-version=7.4" \
        -H "Authorization: Bearer $access_token" \
        -H "Content-Type: application/json")

    echo "$response" | jq -r '.value'
}

set_env_variable() {
    local key="$1"
    local value="$2"

    # Determine the file based on APP_ENV
    local app_env="${APP_ENV:-default}"
    local env_file=".env"

    case "$app_env" in
        beta) env_file=".env.beta" ;;
        production) env_file=".env.production" ;;
    esac

    # If the file exists, we load its contents; if not, we create an empty file
    if [ -f "$env_file" ]; then
        env_content=$(cat "$env_file")
    else
        env_content=""
    fi

    # Make sure the value is enclosed in quotation marks
    local quoted_value="\"$(echo "$value" | sed 's/"/\\"/g')\""

    # If the key already exists, replace its value
    if grep -q "^$key=" "$env_file"; then
        sed -i "s|^$key=.*|$key=$quoted_value|" "$env_file"
    else
        echo "$key=$quoted_value" >> "$env_file"
    fi
}

echo "$(date '+%Y-%m-%d %H:%M:%S') - Retrieving variables from Azure ..."

echo "CLIENT_ID: $CLIENT_ID"
echo "CLIENT_SECRET: $CLIENT_SECRET"
echo "URL_AUTH: $URL_AUTH"
echo "appconfiguration: $appconfiguration"
echo "APPCONFIG_SCOPE: $APPCONFIG_SCOPE"
echo "SUSCRIPTION_ID: $SUSCRIPTION_ID"
echo "RESOURCE_GROUP_NAME: $RESOURCE_GROUP_NAME"
echo "APP_ENV: $APP_ENV"

PROJECT_NAME="apiBaco"
URL_AUTH="$URL_AUTH"
CLIENT_ID="$CLIENT_ID"
CLIENT_SECRET="$CLIENT_SECRET"
APPCONFIG_SCOPE="$APPCONFIG_SCOPE"

# Get an Access Token for AppConfig
APP_CONFIG_TOKEN=$(get_access_token "$URL_AUTH" "$CLIENT_ID" "$CLIENT_SECRET" "$APPCONFIG_SCOPE")

if [[ -n "$APP_CONFIG_TOKEN" ]]; then
    log "Token loaded from AppConfigToken."
    APP_CONFIG="$appconfiguration"
    APP_CONFIG_DATA=$(get_app_config "$APP_CONFIG_TOKEN" "$PROJECT_NAME" "$APP_CONFIG")

    # Auxiliary variables
    KEYVAULT=""
    KEYVAULT_SCOPE=""

    # We store the output in a temporary variable
    TEMP_DATA=$(echo "$APP_CONFIG_DATA" | jq -c '.items[]')

    while read -r item; do
        KEY=$(echo "$item" | jq -r '.key')
        VALUE=$(echo "$item" | jq -r '.value')
        ENV_KEY=${KEY#$PROJECT_NAME\_}

        if [[ "$ENV_KEY" == "KEYVAULT" ]]; then
            KEYVAULT="$VALUE"
            KEYVAULT_SCOPE="${VALUE}.default"
        else
            set_env_variable "$ENV_KEY" "$VALUE"
        fi
    done <<< "$TEMP_DATA"
fi

# Check whether KEYVAULT and KEYVAULT_SCOPE have been assigned
if [[ -z "$KEYVAULT" || -z "$KEYVAULT_SCOPE" ]]; then
    log "ERROR: Unable to retrieve the Key Vault URL or its scope. Check AppConfig."
    exit 1
fi

#echo 'exit' | telnet etcprdkeyvaul-apichgadd.vault.azure.net 443

# Get an Access Token for Key Vault
KEYVAULT_TOKEN=$(get_access_token "$URL_AUTH" "$CLIENT_ID" "$CLIENT_SECRET" "$KEYVAULT_SCOPE")

if [[ -n "$KEYVAULT_TOKEN" ]]; then
    log "Token loaded from KeyVaultToken."

    # Retrieve secrets from Key Vault
    KEYVAULT_SECRETS=$(get_secrets_from_keyvault "$KEYVAULT" "$KEYVAULT_TOKEN")

    # Check if the response contains data
    if [[ -z "$KEYVAULT_SECRETS" || "$KEYVAULT_SECRETS" == "null" ]]; then
        log "ERROR: No secrets were received from Key Vault. Check the permissions and the URL."
        exit 1
    fi

    if jq -e . >/dev/null 2>&1 <<< "$KEYVAULT_SECRETS"; then
        # We store the output in a variable to avoid creating a subshell
        TEMP_SECRETS=$(echo "$KEYVAULT_SECRETS" | jq -c '.value[]')
        while read -r secret; do
            SECRET_URL=$(echo "$secret" | jq -r '.id' | tr -d ' ')
            SECRET_NAME=$(basename "$SECRET_URL" | tr -d ' ')
            SECRET_NAME=${SECRET_NAME#$PROJECT_NAME}
            SECRET_VALUE=$(get_value_secret_from_keyvault "$SECRET_URL" "$KEYVAULT_TOKEN")

            log "SECRET_VALUE $SECRET_NAME + $SECRET_VALUE"  

        if [[ -n "$SECRET_VALUE" ]]; then
            set_env_variable "$SECRET_NAME" "$SECRET_VALUE"
        else
            log "Warning: The secret $SECRET_NAME is empty or could not be retrieved."
            log "Try manually using:"
            log "curl -s -X GET '$SECRET_URL?api-version=7.4' -H 'Authorization: Bearer $KEYVAULT_TOKEN'"
        fi
        done <<< "$TEMP_SECRETS"

        log "Azure variables have been loaded successfully."
    else
        echo "ERROR: KEYVAULT_SECRETS does not contain valid JSON"
        echo "Content received: $KEYVAULT_SECRETS"
        exit 1
    fi

else
    log " ERROR: Unable to retrieve the token from the Key Value."
    exit 1
fi

exec "$@"
