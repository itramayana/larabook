<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Entrust;
use App\Author;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


class HomeController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        //Mengecek mode user login
        if (Entrust::hasRole('admin')) return $this->adminDashboard();
        if (Entrust::hasRole('member')) return $this->memberDashboard();
        return view('home');
    }

    //jika yang login = admin
    protected function adminDashboard()
    {
        $authors = [];
        $books = [];
        foreach (Author::all() as $author)
        {
            array_push($authors, $author->name);
            array_push($books, $author->books->count());
        }
        return view('dashboard.admin', compact('authors', 'books'));
    }

    //jika yang login = member
    protected function memberDashboard()
    {
        //Menampilkan data buku yang sudah terpinjam
        $borrowLogs = Auth::user()->borrowLogs()->borrowed()->get();
        return view('dashboard.member', compact('borrowLogs'));

    }
}
