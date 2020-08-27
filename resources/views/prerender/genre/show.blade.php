@extends('common::prerender.base')

<?php /** @var App\Services\Meta\MetaTags $meta */ ?>

@section('body')
    <h1>{{ $meta->getTitle() }}</h1>

    <ul class="artists">
        @foreach($meta->getData('artists.data') as $artist)
            <li>
                <figure>
                    <img src="{{$artist['image_small']}}">
                    <figcaption><a href="{{$meta->urls->artist($artist)}}">{{$artist['name']}}</a></figcaption>
                </figure>
            </li>
        @endforeach
    </ul>
@endsection
