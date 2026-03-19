<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\JsonPlaceHolderService;

class JsonPlaceHolderController extends Controller
{
    public function __construct(private JsonPlaceHolderService $getJsonPlaceHolder)
    {
    }

    public function getUsers() {
      $users = $this->getJsonPlaceHolder->getJsonFromJPlaceHolder('users');
      dd($users);
    }

    public function getPosts() {
        $posts = $this->getJsonPlaceHolder->getJsonFromJPlaceHolder('posts');
        dd($posts);
    }
}
