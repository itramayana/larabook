<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Author;
use Yajra\Datatables\Html\Builder;
use Yajra\Datatables\Datatables;
use Session;

class AuthorsController extends Controller
{
    public function index(Request $request, Builder $htmlBuilder)
    {
        if ($request->ajax())
        {
            $authors = Author::select(['id', 'name']);
            return Datatables::of($authors)
                ->addColumn('action', function($author){
                    return view('datatable._action', [
                        'model' => $author,
                        'form_url' => route('admin.authors.destroy', $author->id),
                        'edit_url' => route('admin.authors.edit', $author->id),
                        'confirm_message' => 'Yakin mau menghapus ' . $author->name . '?'
                    ]);
                })->make(true);

        }
        $html = $htmlBuilder
            ->addColumn(['data'=>'name','name'=>'name','title'=>'Nama'])
            ->addColumn(['data'=>'action','name'=>'action','title'=>'Action','orderable'=>false
                ,'searchable'=>false]);
        return view('authors.index')->with(compact('html'));

    }


    public function create()
    {
        return view('authors.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, ['name' => 'required|unique:authors']);
        $author = Author::create($request->only('name'));
        Session::flash("flash_notification",[
            "level"=>"success",
            "message"=>"Berhasil menyimpan $author->name"
        ]);
        return redirect()->route('admin.authors.index');
    }

    public function show($id)
    {

    }

    public function edit($id)
    {
        $author = Author::find($id);
        return view('authors.edit')->with(compact('author'));


    }

    public function update(Request $request, $id)
    {
        $this->validate($request, ['name' => 'required|unique:authors,name,'. $id]);
        $author = Author::find($id);
        $author->update($request->only('name'));
        Session::flash("flash_notification", [
            "level"=>"success",
            "message"=>"Berhasil menyimpan $author->name"
        ]);
        return redirect()->route('admin.authors.index');

    }

    public function destroy($id)
    {
        if(!Author::destroy($id)) return redirect()->back();
        
        Session::flash("flash_notification", [ 
            "level"=>"success", 
            "message"=>"Penulis berhasil dihapus"
        ]);
        
        return redirect()->route('admin.authors.index');
    }

    public function verify(Request $request, $token)
    {

    }
}
