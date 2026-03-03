<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class SaleDocument extends Model
{
    protected $table = 'sale_documents';
    protected $fillable = [
        'sale_id', 
        'document_name', 
        'document_path', 
        'document_size', 
        'document_extension',
		'user_id'
    ];
}
