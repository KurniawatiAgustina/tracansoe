<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\category;
use App\Models\CategoryBlog;
use App\Models\promosi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LandingPageController extends Controller
{
    public function landingPage()
    {
        // Get current date
        $currentDate = date('Y-m-d');

        // Query active promo
        $activePromo = promosi::where('start_date', '<=', $currentDate)
            ->where('end_date', '>=', $currentDate)
            ->where('status', 'active')
            ->first();

        // Query upcoming promos
        $upcomingPromos = promosi::where('start_date', '>', $currentDate)
            ->where('status', 'upcoming')
            ->get();

        // Query expired promos
        $expiredPromos = promosi::where('end_date', '<', $currentDate)
            ->where('status', 'expired')
            ->get();

        // Query categories including their subcategories
        $categories = category::with('subKriteria') // Ambil subkategori
            ->whereNull('parent_id') // Hanya ambil kategori induk
            ->get();

        $blog = Blog::with('category')->where('status_publish', 'published')->latest()->take(6)->get();
        // dd($blog);

        return view('LandingPage.index', compact('activePromo', 'upcomingPromos', 'expiredPromos', 'categories', 'blog'));
    }

    public function index(Request $request)
    {
        // Mengambil kategori dari database
        $categories = CategoryBlog::all();

        // Query dasar untuk blog, tambahkan eager loading untuk relasi user dan category
        $blogsQuery = Blog::with('user', 'category')
            ->where('status_publish', 'published');

        // Filter berdasarkan kategori jika ada input category
        if ($request->has('category') && $request->category) {
            $blogsQuery->where('category_blog_id', $request->category);
        }

        // Filter berdasarkan tanggal jika ada input filter tanggal
        if ($request->has('filter_date') && $request->filter_date) {
            $blogsQuery->whereDate('date_publish', $request->filter_date);
        }

        // Pagination dengan 6 blog per halaman
        $blogs = $blogsQuery->orderBy('date_publish', 'desc')
            ->paginate(6);

        // Mengambil 4 popular posts terbaru berdasarkan tanggal publikasi
        $popularPosts = Blog::with('category')
            ->where('status_publish', 'published')
            ->orderBy('date_publish', 'desc')
            ->limit(4)
            ->get();

        return view('LandingPage.blog', compact('blogs', 'categories', 'popularPosts'));
    }

    public function showBlog($slug)
    {
        $blog = Blog::where('slug', $slug)->published()->first(); // Check if blog is published
        if (!$blog) {
            return redirect()->back()->with('error', 'Blog tidak ditemukan atau belum dipublikasikan');
        }

        return view('LandingPage.detail-blog', compact('blog'));
    }

}