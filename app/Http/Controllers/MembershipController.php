<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Members;
use App\Models\MembersTrack;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MembershipController extends Controller
{

    public function register(Request $request)
    {
        // Validasi input dari form
        $validatedData = $request->validate([
            'nama_membership' => 'required|string|max:255',
            'email_membership' => 'required|email|max:255',
            'phone_membership' => 'required|string|max:15',
            'alamat_membership' => 'required|string|max:255',
            'buktiPembayaran' => 'required|file|mimes:jpg,png,pdf|max:2048',
            'kelas_membership' => 'required|in:standard,gold,premium',
            'totalPembayaran' => 'required|numeric|min:0',
        ]);

        // Proses upload file bukti pembayaran
        if ($request->hasFile('buktiPembayaran')) {
            $file = $request->file('buktiPembayaran');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $buktiPembayaranPath = $file->storeAs('bukti_pembayaran', $fileName, 'public');
        } else {
            return response()->json(['status' => 'error', 'message' => 'Bukti pembayaran harus diunggah.'], 400);
        }

        // Buat record untuk tabel members
        $member = Members::create([
            'nama_membership' => $validatedData['nama_membership'],
            'email_membership' => $validatedData['email_membership'],
            'phone_membership' => $validatedData['phone_membership'],
            'alamat_membership' => $validatedData['alamat_membership'],
            'kode' => null,
        ]);

        // Buat record untuk tabel members_tracks
        MembersTrack::create([
            'membership_id' => $member->id,
            'buktiPembayaran' => $buktiPembayaranPath,
            'totalPembayaran' => $validatedData['totalPembayaran'],
            'tanggalPembayaran' => Carbon::now()->toDateString(),
            'jamPembayaran' => Carbon::now()->toTimeString(),
            'start_membership' => null,
            'end_membership' => null,
            'status' => 'waiting',
            'kelas_membership' => $validatedData['kelas_membership'],
            'discount' => null,
        ]);

        // Kembalikan response JSON berhasil
        return response()->json(['status' => 'success', 'message' => 'Membership berhasil didaftarkan.'], 200);
    }

    public function extend(Request $request)
    {
        // Validasi input dari form
        $validatedData = $request->validate([
            'kode' => 'required|string|max:255',
            'buktiPembayaran' => 'required|file|mimes:jpg,png,pdf|max:2048',
            'kelas_membership' => 'required|in:standard,gold,premium',
            'totalPembayaran' => 'required|numeric|min:0',
        ]);

        // Temukan record members berdasarkan kode
        $member = Members::where('kode', $validatedData['kode'])->first();
        if (!$member) {
            return response()->json(['status' => 'error', 'message' => 'Kode membership tidak valid.'], 400);
        }

        // Ambil track terakhir untuk anggota ini
        $memberTrack = MembersTrack::where('membership_id', $member->id)->orderBy('created_at', 'desc')->first();

        if (!$memberTrack) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada track untuk anggota ini.'], 400);
        }

        $statusLast = $memberTrack->status;
        if ($statusLast == 'waiting') {
            return response()->json(['status' => 'error', 'message' => 'Status anda masih Waiting, tidak bisa melakukan perpanjangan.'], 400);
        } elseif ($statusLast == 'active') {
            return response()->json(['status' => 'error', 'message' => 'Status anda masih Active, tidak bisa melakukan perpanjangan.'], 400);
        }

        // Proses upload file bukti pembayaran
        if ($request->hasFile('buktiPembayaran')) {
            $file = $request->file('buktiPembayaran');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $buktiPembayaranPath = $file->storeAs('bukti_pembayaran', $fileName, 'public');
        } else {
            return response()->json(['status' => 'error', 'message' => 'Bukti pembayaran harus diunggah.'], 400);
        }

        // Buat record baru di members_tracks untuk perpanjangan
        MembersTrack::create([
            'membership_id' => $member->id,
            'buktiPembayaran' => $buktiPembayaranPath,
            'totalPembayaran' => $validatedData['totalPembayaran'],
            'tanggalPembayaran' => Carbon::now()->toDateString(),
            'jamPembayaran' => Carbon::now()->toTimeString(),
            'start_membership' => null, // Ini bisa diupdate di fungsi verifikasi
            'end_membership' => null, // Ini bisa diupdate di fungsi verifikasi
            'status' => 'waiting',
            'kelas_membership' => $validatedData['kelas_membership'],
            'discount' => null,
        ]);

        // Kembalikan respons JSON berhasil
        return response()->json(['status' => 'success', 'message' => 'Membership berhasil diperpanjang, menunggu verifikasi.'], 200);
    }

    public function verify(Request $request, $uuid)
    {
        // Temukan record members berdasarkan uuid
        $member = Members::where('uuid', $uuid)->firstOrFail();
        // Ambil track membership terakhir
        $membersTrack = MembersTrack::where('membership_id', $member->id)
            ->orderBy('created_at', 'desc')
            ->firstOrFail(); // Ambil record terakhir

        // Tentukan durasi membership berdasarkan kelas yang dipilih
        $membershipDuration = 0;
        $kodeMembershipPrefix = '';

        switch ($membersTrack->kelas_membership) {
            case 'standard':
                $membershipDuration = 3; // 3 bulan untuk standard
                $kodeMembershipPrefix = 'ST';
                break;
            case 'gold':
                $membershipDuration = 6; // 6 bulan untuk gold
                $kodeMembershipPrefix = 'GL';
                break;
            case 'premium':
                $membershipDuration = 12; // 12 bulan untuk premium
                $kodeMembershipPrefix = 'PR';
                break;
        }

        // Cek apakah kode sudah ada
        if (is_null($member->kode)) {
            // Hitung nomor urut untuk kode membership
            $lastMembership = Members::where('kode', 'like', $kodeMembershipPrefix . '-%')
                ->orderBy('created_at', 'desc')
                ->first();

            $nextNumber = $lastMembership ? (intval(substr($lastMembership->kode, 3, 4)) + 1) : 1; // Nomor berikutnya

            // Format nomor urut menjadi 4 digit
            $formattedNumber = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Generate kode unik dengan format
            $kodeMembership = $kodeMembershipPrefix . '-' . $formattedNumber . '-' . Str::random(4); // Tambahkan 4 karakter acak

            // Simpan kode unik di model Members
            $member->update([
                'kode' => $kodeMembership, // Simpan UUID sebagai kode unik
            ]);
        }

        // Update status, start_membership, end_membership, discount di members_track
        $membersTrack->update([
            'status' => 'active', // Ubah status menjadi active setelah verifikasi
            'start_membership' => Carbon::now()->toDateString(),
            'end_membership' => Carbon::now()->addMonths($membershipDuration)->toDateString(),
            'discount' => $membersTrack->kelas_membership == 'standard' ? 0.05 : ($membersTrack->kelas_membership == 'gold' ? 0.10 : 0.15),
        ]);

        // Redirect atau response untuk admin
        return redirect()->route('memberships.show', $member->uuid)->with('success', 'Membership berhasil diverifikasi.');
    }





    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('membership.index');
    }

    public function list(Request $request)
    {
        if ($request->ajax()) {
            $dataPlusService = Members::select("uuid", "nama_membership", "email_membership")->get();
            return DataTables::of($dataPlusService)
                ->addIndexColumn()
                ->make(true);
        }
        return response()->json(['message' => 'Method not allowed'], 405);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        $member = Members::where('uuid', $uuid)->firstOrFail();
        $membersTrack = MembersTrack::where('membership_id', $member->id)->get(); // Ambil semua record members_track

        return view('membership.show', compact('member', 'membersTrack')); // Sertakan membersTrack
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
