<?php

namespace App\Models\Mysql\Casper;

use Illuminate\Database\Eloquent\Model;

class UserScopes extends Model
{

    const NAMECONNECTION = 'casper';

    const NAMETABLE  = 'user_scopes';

    /**
     * connection
     *
     * @var string
     */
    protected $connection = self::NAMECONNECTION;

    /**
     * timestamps
     *
     * @var bool
     */
    public $timestamps = false;
}
