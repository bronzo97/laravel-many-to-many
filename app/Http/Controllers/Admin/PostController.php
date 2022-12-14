<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use App\Http\Controllers\Controller;
use App\Mail\NewPostMail;
use App\Post;
use App\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Ritorna un elemento cercandolo per il suo slug.
     * Se non viene trovato, viene lanciato un errore 404
     */
    private function findBySlug($slug)
    {
        $post = Post::where("slug", $slug)->first();

        if (!$post) {
            abort(404);
        }

        return $post;
    }

    private function generateSlug($text)
    {
        $toReturn = null;
        $counter = 0;

        do {
            // generiamo uno slug partendo dal titolo
            $slug = Str::slug($text);

            // se il counter é maggiore di 0, concateno il suo valore allo slug
            if ($counter > 0) {
                $slug .= "-" . $counter;
            }

            // controllo a db se esiste già uno slug uguale
            $slug_esiste = Post::where("slug", $slug)->first();

            if ($slug_esiste) {
                // se esiste, incremento il contatore per il ciclo successivo
                $counter++;
            } else {
                // Altrimenti salvo lo slug nei dati del nuovo post
                $toReturn = $slug;
            }
        } while ($slug_esiste);

        return $toReturn;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $posts = Post::orderBy("created_at", "desc")->get();
        $user = Auth::user();
        $posts = $user->posts;

        return view("admin.posts.index", compact("posts"));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view("admin.posts.create", compact("categories", "tags"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // validare i dati ricevuti
        $validatedData = $request->validate([
            "title" => "required|min:10",
            "content" => "required|min:10"
        ]);

        // Salvare a db i dati
        $post = new Post();
        $post->fill($validatedData);
        $post->user_id = Auth::user()->id;

        $post->slug = $this->generateSlug($post->title);

        $post->save();

        Mail::to($post->user->email)->send(new NewPostMail($post));

        // redirect su una pagina desiderata - di solito show
        return redirect()->route("admin.posts.show", $post->slug);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($slug)
    {
        $post = $this->findBySlug($slug);

        return view("admin.posts.show", compact("post"));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($slug)
    {
        $post = $this->findBySlug($slug);
        $categories = Category::all();
        $tags = Tag::all();

        return view("admin.posts.edit", compact("post", "categories", "tags"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $slug)
    {

        // dd($request->all());

        $validatedData = $request->validate([
            "title" => "required|min:10",
            "content" => "required|min:10",
            "category_id" => "nullable|exists:categories,id",
            "tags" => "nullable|exists:tags,id",
            "cover_img" => "nullable|image"
        ]);
        $post = $this->findBySlug($slug);

        if(key_exists("cover_img", $validatedData)) {

            if($post->cover_img) {
                Storage::delete($post->cover_img);
            };

            $coverImg = Storage::put("/", $validatedData["cover_img"]);

            $post->cover_img = $coverImg;
        }

        if ($validatedData["title"] !== $post->title) {
            $post->slug = $this->generateSlug($validatedData["title"]);
        }

        if (key_exists("tags", $validatedData)) {
            $post->tags()->sync($validatedData["tags"]);
        } else {
            $post->tags()->sync([]);
        }

        $post->update($validatedData);

        return redirect()->route("admin.posts.show", $post->slug);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($slug)
    {
        $post = $this->findBySlug($slug);

        $post->tags()->detach();

        $post->delete();

        return redirect()->route("admin.posts.index");
    }
}