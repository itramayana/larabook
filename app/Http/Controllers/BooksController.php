<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\Datatables\Html\Builder;
use Yajra\Datatables\Datatables;
use App\Book;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use League\Flysystem\FileNotFoundException;
use App\Author;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\ModelNotFoundException; 
use App\BorrowLog; 
use Illuminate\Support\Facades\Auth;
use App\Exceptions\BookException;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade as PDF;



class BooksController extends Controller
{

    //Membuat value yang akan di input ke Datatable
    public function index(Request $request, Builder $htmlBuilder)
    {
        if ($request->ajax()) {
            $books = Book::with('author');
            return Datatables::of($books) ->addColumn('action', function($book)
            {
                return view('datatable._action', [
                    'model' => $book,
                    'form_url' => route('admin.books.destroy', $book->id),
                    'edit_url' => route('admin.books.edit', $book->id),
                    'confirm_message' => 'Yakin mau menghapus ' . $book->title . '?' ]);
            })->make(true); }
        $html = $htmlBuilder
            ->addColumn(['data' => 'title', 'name'=>'title', 'title'=>'Judul'])
            ->addColumn(['data' => 'amount', 'name'=>'amount', 'title'=>'Jumlah'])
            ->addColumn(['data' => 'author.name', 'name'=>'author.name', 'title'=>'Penulis'])
            ->addColumn(['data' => 'action', 'name'=>'action', 'title'=>'Action', 'orderable'=>false, 'searchable'=>false,]);
        return view('books.index')->with(compact('html'));
    }

    public function create()
    {
        return view('books.create');
    }

    public function store(StoreBookRequest $request)
    {
        $book = Book::create($request->except('cover'));

        // isi field cover jika ada cover yang diupload
        if ($request->hasFile('cover')) {

            //Mengambil file yang diupload
            $uploaded_cover = $request->file('cover');

            // mengambil extension file
            $extension = $uploaded_cover->getClientOriginalExtension();

            // membuat nama file random dengan extension
            $filename = md5(time()) . '.' . $extension;
            $destinationPath = public_path() . DIRECTORY_SEPARATOR . 'img';

            // memindahkan file ke folder public/img
            $uploaded_cover->move($destinationPath, $filename);

            // mengisi field cover di book dengan filename yang baru dibuat
            $book->cover = $filename;
            $book->save();
        }

        Session::flash("flash_notification", [
            "level"=>"success",
            "message"=>"Berhasil menyimpan $book->title"
        ]);

        return redirect()->route('admin.books.index');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $book = Book::find($id);
        return view('books.edit')->with(compact('book'));
    }


    public function update(Request $request, $id)
    {

        $book = Book::find($id);
        //if(!$book->update($request->all())) return redirect()->back();
        if(!$book->update($request->all())) return redirect()->back();


        dd($request->all());
        if ($request->hasFile('cover')) {
            $filename = null;
            $uploaded_cover = $request->file('cover');
            $extension = $uploaded_cover->getClientOriginalExtension();

            // membuat nama file random dengan extension
            $filename = md5(time()) . '.' . $extension;
            $destinationPath = public_path() . DIRECTORY_SEPARATOR . 'img';

            // memindahkan file ke folder public/img
            $uploaded_cover->move($destinationPath, $filename);

            // hapus cover lama, jika ada
            if ($book->cover) {
                $old_cover = $book->cover;
                $filepath = public_path() . DIRECTORY_SEPARATOR . 'img'
                    . DIRECTORY_SEPARATOR . $book->cover;

                try {
                    File::delete($filepath);
                } catch (FileNotFoundException $e) {
                    // File sudah dihapus/tidak ada
                }
            }

            // ganti field cover dengan cover yang baru
            $book->cover = $filename;
            $book->save();
        }

        Session::flash("flash_notification", [
            "level"=>"success",
            "message"=>"Berhasil menyimpan $book->title"
        ]);

        return redirect()->route('admin.books.index');
    }

    //Untuk mengahapus Cover
    public function destroy(Request $request, $id)
    {
        $book = Book::find($id);
        $cover = $book->cover;
        if(!$book->delete()) return redirect()->back();


        // handle hapus buku via ajax
        if ($request->ajax()) return response()->json(['id' => $id]);

        // hapus cover lama, jika ada
        if ($cover)
        {
            $old_cover = $book->cover;
            $filepath = public_path() . DIRECTORY_SEPARATOR . 'img'
            . DIRECTORY_SEPARATOR . $book->cover;
            try
            {
                File::delete($filepath);
            }
            catch (FileNotFoundException $e)
            {
                // File sudah dihapus/tidak ada
            }
        }
        Session::flash("flash_notification", [
            "level" => "success",
            "message" => "Buku berhasil dihapus"
        ]);

        return redirect()->route('admin.books.index');
    }

    public function borrow($id)
    {
        try {
            $book = Book::findOrFail($id);
            
            Auth::user()->borrow($book);
            Session::flash("flash_notification", [
                "level"=>"success",
                "message"=>"Berhasil meminjam $book->title"
            ]);
        }

        catch (BookException $e) {
            Session::flash("flash_notification", [
                "level" => "danger",
                "message" => $e->getMessage()
            ]);
        }
        
        catch (ModelNotFoundException $e) {
            Session::flash("flash_notification", [
                "level"=>"danger",
                "message"=>"Buku tidak ditemukan."
            ]);
        }
        return redirect('/');
    }

    public function returnBack($book_id)
    {
        $borrowLog = BorrowLog::where('user_id', Auth::user()->id)
            ->where('book_id', $book_id)
            ->where('is_returned', 0)
            ->first();

        if($borrowLog)
        {
            $borrowLog->is_returned = true;
            $borrowLog->save();

            Session::flash("flash_notification", [
                "level"=>"success",
                "message"=>"Berhasil mengembalikan " . $borrowLog->book->title
            ]);
        }
        return redirect('/home');
    }

    //method untuk export ke excel
    public function export()
    {
        return view('books.export');
    }


    //method untuk export ke excel
    public function exportPost(Request $request)
    {
        //validasi
        $this->validate($request,[
            'author_id'=>'required',
            'type'=>'required|in:pdf,xlsx'
        ],[
            'author_id.required'=>'Anda bekum memiliki penulis. Pilih minimal 1 penulis.'
        ]);

        $books = Book::whereIn('id', $request->get('author_id'))->get();

        $handler = 'export' . ucfirst($request->get('type'));
        return $this->$handler($books);
    }

    //Untuk Export Excel
    private function exportXls($books)
    {
        Excel::create('Data Buku Larapus', function($excel) use ($books) {
        // Set the properties
            $excel->setTitle('Data Buku Larapus')
                ->setCreator('Rahmat Awaludin');
            $excel->sheet('Data Buku', function($sheet) use ($books) {
                $row = 1;
                $sheet->row($row, [
                    'Judul',
                    'Jumlah',
                    'Stok',
                    'Penulis'
                ]);
                foreach ($books as $book) {
                    $sheet->row(++$row, [
                        $book->title,
                        $book->amount,
                        $book->stock,
                        $book->author->name
                    ]);
                }
            });
        })->export('xlsx');
    }

    //Untuk export PDF
    private function exportPdf($books)
    {
        $pdf = PDF::loadview('pdf.books', compact('books'));
        return $pdf->download('books.pdf');
    }

    //Untuk membuat template Excel
    public function generateExcelTemplate()
    {
        Excel::create('Template Import Buku', function($excel) {
    // Set the properties
            $excel->setTitle('Template Import Buku')
                ->setCreator('Larabook')
                ->setCompany('Larabook')
                ->setDescription('Template import buku untuk Larabook');
            $excel->sheet('Data Buku', function($sheet) {
                $row = 1;
                $sheet->row($row, [
                    'judul',
                    'penulis',
                    'jumlah'
                ]);
            });
        })->export('xlsx');
    }

    //Untuk Import Excel
    public function importExcel(Request $request)
    {
        // validasi untuk memastikan file yang diupload adalah excel
        $this->validate($request, [ 'excel' => 'required|mimes:xlsx' ]);
    // ambil file yang baru diupload
        $excel = $request->file('excel');
    // baca sheet pertama
        $excels = Excel::selectSheetsByIndex(0)->load($excel, function($reader) {
    // options, jika ada
        })->get();

        // rule untuk validasi setiap row pada file excel
        $rowRules = [
            'judul' => 'required',
            'penulis' => 'required',
            'jumlah' => 'required'
        ];
        // Catat semua id buku baru
        // ID ini kita butuhkan untuk menghitung total buku yang berhasil diimport
        $books_id = [];
        // looping setiap baris, mulai dari baris ke 2 (karena baris ke 1 adalah nama kolom)
        foreach ($excels as $row) {
            // Membuat validasi untuk row di excel
            // Disini kita ubah baris yang sedang di proses menjadi array
            $validator = Validator::make($row->toArray(), $rowRules);
            // Skip baris ini jika tidak valid, langsung ke baris selanjutnya
            if ($validator->fails()) continue;
            // Syntax dibawah dieksekusi jika baris excel ini valid
            // Cek apakah Penulis sudah terdaftar di database
            $author = Author::where('name', $row['penulis'])->first();
            // buat penulis jika belum ada
            if (!$author) {
                $author = Author::create(['name'=>$row['penulis']]);
            }
            // buat buku baru
            $book = Book::create([
                'title' => $row['judul'],
                'author_id' => $author->id,
                'amount' => $row['jumlah']
            ]);
            // catat id dari buku yang baru dibuat
            array_push($books_id, $book->id);
        }
        // Ambil semua buku yang baru dibuat
        $books = Book::whereIn('id', $books_id)->get();
        // redirect ke form jika tidak ada buku yang berhasil diimport
        if ($books->count() == 0) {
            Session::flash("flash_notification", [
                "level" => "danger",
                "message" => "Tidak ada buku yang berhasil diimport."
            ]);
            return redirect()->back();
        }
        // set feedback
        Session::flash("flash_notification", [
            "level" => "success",
            "message" => "Berhasil mengimport " . $books->count() . " buku."
        ]);
        // Tampilkan halaman review Buku
        return view('books.import-review')->with(compact('books'));
    }
}
