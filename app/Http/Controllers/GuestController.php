<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Yajra\Datatables\Html\Builder;
use Yajra\Datatables\Datatables;
use App\Book;
use Entrust;

class GuestController extends Controller
{
    public function index(Request $request, Builder $htmlBuilder)
    {
        if ($request->ajax()) {
            $books = Book::with('author');
            return Datatables::of($books)

                //Membuat kolom untuk menampung nilai stok
                ->addColumn('stock', function ($book){
                    return $book->stock;
                })

                ->addColumn('borrowed', function ($book){
                    return $book->borrowed;
                })



                ->addColumn('action', function ($book) {
                    //Syntax untuk tombol pinjam yang tampil jika user bukan admin
                    if (Entrust::hasRole('admin')) return '';
                    return '<a class="btn btn-xs btn-primary" href="'.route('books.borrow', $book->id).'">Pinjam</a>';
                })->make(true);
        }
        $html = $htmlBuilder
            ->addColumn(['data' => 'title', 'name' => 'title', 'title' => 'Judul'])
            ->addColumn(['data' => 'author.name', 'name' => 'author.name', 'title' => 'Penulis'])
            ->addColumn(['data' => 'amount', 'name' => 'amount', 'title' => 'Jumlah Total'])
            ->addColumn(['data' => 'borrowed', 'name' =>  'borrowed', 'title' => 'Terpinjam'])
            ->addColumn(['data' => 'stock', 'name' => 'stock', 'title' => 'Stok', 'orderable'=>false,'searchable'=>false])
            ->addColumn(['data' => 'action', 'name' => 'action', 'title' => 'Action', 'orderable' => false,
                'searchable' => false]);
        return view('guest.index')->with(compact('html'));
    }
}
