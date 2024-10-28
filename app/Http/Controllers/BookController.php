<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::select('books.*', 'genres.name as genre')
            ->join('genres', 'genres.id', '=', 'books.genre_id')
            ->paginate(10);

        return response()->json($books);
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|min:1|max:100',
            'isbn' => 'required|string|min:1|max:100',
            'genre_id' => 'required|numeric',
            'pages' => 'required|integer|min:1',
            'image_path' => 'nullable|image|max:2048',
        ];

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all()
            ], 400);
        }

        // Crear la instancia del libro
        $book = new Book();

        // Asignar los valores del request al libro
        $book->name = $request->input('name');
        $book->isbn = $request->input('isbn');
        $book->genre_id = $request->input('genre_id');
        $book->pages = $request->input('pages');

        // Manejar la subida de la imagen
        if ($request->hasFile('image_path')) {
            $file = $request->file('image_path');
            $filename = time() . '_' . $file->getClientOriginalName();
            
            // Verificar si la carpeta public/images existe
            if (!file_exists(public_path('images'))) {
                mkdir(public_path('images'), 0755, true);
            }

            $file->move(public_path('images'), $filename);
            $book->image_path = 'images/' . $filename; // Guardar la ruta de la imagen en la base de datos
        }

        // Guardar el libro en la base de datos
        $book->save();

        return response()->json([
            'status' => true,
            'message' => 'Book created successfully',
            'book' => $book,
        ], 200);
    }

    public function show(Book $book)
    {
        return response()->json(['status' => true, 'data' => $book]);
    }

    public function update(Request $request, Book $book)
{
    $rules = [
        'name' => 'required|string|min:1|max:100',
        'isbn' => 'required|string|min:1|max:100',
        'genre_id' => 'required|numeric',
        'pages' => 'required|integer|min:1',
        'image_path' => 'nullable|image|max:2048',
    ];

    $validator = \Validator::make($request->all(), $rules);
    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()->all()
        ], 400);
    }

    // Actualizar los campos del libro
    $book->name = $request->input('name');
    $book->isbn = $request->input('isbn');
    $book->genre_id = $request->input('genre_id');
    $book->pages = $request->input('pages');

    // Manejar la actualización de la imagen si existe
    if ($request->hasFile('image_path')) {
        // Eliminar la imagen anterior si existe
        if ($book->image_path && file_exists(public_path($book->image_path))) {
            unlink(public_path($book->image_path));
        }

        // Subir la nueva imagen
        $file = $request->file('image_path');
        $filename = time() . '_' . $file->getClientOriginalName();
        
        if (!file_exists(public_path('images'))) {
            mkdir(public_path('images'), 0755, true);
        }

        $file->move(public_path('images'), $filename);
        $book->image_path = 'images/' . $filename; // Actualizar la ruta de la imagen
    }

    // Guardar los cambios en la base de datos
    $book->save();

    return response()->json([
        'status' => true,
        'message' => 'Book updated successfully',
        'book' => $book
    ], 200);
}


    public function destroy(Book $book)
    {
        // Eliminar la imagen si existe
        if ($book->image_path && file_exists(public_path($book->image_path))) {
            unlink(public_path($book->image_path));
        }

        // Eliminar el libro
        $book->delete();

        return response()->json([
            'status' => true,
            'message' => 'Book deleted successfully'
        ], 200);
    }

    public function booksByGenre()
    {
        $books = Book::select(DB::raw('count(books.id) as count'), 'genres.name')
            ->rightJoin('genres', 'genres.id', '=', 'books.genre_id')
            ->groupBy('genres.name')
            ->get();

        return response()->json($books);
    }

    public function all()
    {
        $books = Book::select('books.*', 'genres.name as genre')
            ->join('genres', 'genres.id', '=', 'books.genre_id')
            ->get();

        return response()->json($books);
    }
}
