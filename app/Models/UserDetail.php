<?php

namespace App\Models;

use App\Helpers\FileHandle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class UserDetail extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\UserDetailFactory::new();
    }
    public function getPictureAttribute($value)
    {
        if($value==null){
            $url = url('/').'/cms-assets/images/navbar-topbar/user.jpg';
            if(config('app.mode')!=="local"){
                return str_replace('http:','https:',$url);
            } else {
                return $url;
            }
        }
        // Always use FileHandle which now generates secure local URLs
        return FileHandle::getURL($value, 0);
    }
}
