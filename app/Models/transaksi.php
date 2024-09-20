<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class transaksi extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'status', 'nama_customer', 'email_customer', 'notelp_customer', 'alamat_customer', 'promosi_id', 'total_harga', 'tracking_number', 'downpayment_amount', 'remaining_payment'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    // Relasi ke tracking_statuses (One-to-Many)
    public function trackingStatuses()
    {
        return $this->hasMany(tracking_status::class);
    }

    // Relasi ke promosi (Many-to-One)
    public function promosi()
    {
        return $this->belongsTo(promosi::class);
    }
    public function karyawan()
    {
        return $this->belongsTo(User::class);
    }

    public function categoryHargas()
    {
        return $this->belongsToMany(category::class, 'transaksi_category_hargas')->withPivot('qty');
    }


    public function plusServices()
    {
        return $this->belongsToMany(plus_service::class, 'transaksi_plus_services')->withPivot('uuid');
    }
}
