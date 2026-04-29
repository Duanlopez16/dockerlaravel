<?php

namespace App\Models\Mysql\Casper;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const NAMECONNECTION = 'casper';

    const NAMETABLE  = 'users';

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
