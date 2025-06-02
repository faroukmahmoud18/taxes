<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Expense extends Model
{
    use HasFactory;
    use HasTranslations; 

    public $translatable = ['description']; 

    protected $fillable = [
        'user_id',
        'description',
        'amount',
        'expense_date',
        'category',
        'is_business_expense',
        'receipt_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2', 
        'expense_date' => 'date',
        'is_business_expense' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
