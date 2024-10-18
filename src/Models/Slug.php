<?php

namespace Ka4ivan\Sluggable\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slug extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [
        'id'
    ];
}
