<?php

namespace App\Models\Mysql\Subscriptions;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    const NAMECONNECTION = 'subscriptions';

    const NAMETABLE  = 'document_types';

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
