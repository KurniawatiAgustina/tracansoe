<?php

namespace App\Http\Controllers;

use App\Models\advice;
use App\Models\transaksi;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $totalTransaksi = Transaksi::count();
        $jumlahKodePromosiDigunakan = Transaksi::whereNotNull('promosi_id')->count();
        $totalPaid = Transaksi::where('status', 'paid')->sum('total_harga');
        // dd($totalPaid);
        $totalOutstanding = Transaksi::where('status', 'downpayment')->sum('remaining_payment'); // Menghitung total outstanding payments
        $advice = Advice::select("nama", "advice")
            ->latest() // Asumsi ada kolom `created_at`
            ->take(3) // Ambil hanya tiga entri
            ->get();
        // buatkan jumlah pendapatan dari model transaksi per bulan
        // Menghitung total pendapatan untuk setiap bulan dalam setahun
        $year = now()->year; // Tahun sekarang
        $totalPendapatanPerBulan = [];

        for ($month = 1; $month <= 12; $month++) {
            $totalPendapatanPerBulan[] = Transaksi::whereMonth('tanggal_transaksi', $month)
                ->whereYear('tanggal_transaksi', $year)
                ->sum('total_harga');
        }
        // dd($monthlyIncome);

        $transaksiWithoutPromosi = $totalTransaksi - $jumlahKodePromosiDigunakan;
        // dd($transaksiWithoutPromosi);

        $promosiData = [
            'Dengan Kode Promosi' => $jumlahKodePromosiDigunakan,
            'Tanpa Kode Promosi' => $transaksiWithoutPromosi
        ];
        return view('Dashboard.index', compact('totalTransaksi', 'jumlahKodePromosiDigunakan', 'totalPaid', 'totalOutstanding', 'advice', 'totalPendapatanPerBulan', 'promosiData'));
    }
}
