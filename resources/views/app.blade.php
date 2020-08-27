@extends('common::framework')

@section('angular-styles')
    {{--angular styles begin--}}
		<link rel="stylesheet" href="client/styles.5b9f63576f9c0f0abca4.css">
	{{--angular styles end--}}
@endsection

@section('angular-scripts')
    {{--angular scripts begin--}}
		<script>setTimeout(function() {
        var spinner = document.querySelector('.global-spinner');
        if (spinner) spinner.style.display = 'flex';
    }, 100);</script>
		<script src="client/runtime-es2015.f28e549543481f719284.js" type="module"></script>
		<script src="client/runtime-es5.f28e549543481f719284.js" nomodule defer></script>
		<script src="client/polyfills-es5.3a5483d56039d9afc051.js" nomodule defer></script>
		<script src="client/polyfills-es2015.216b09f06a2a0bf2403d.js" type="module"></script>
		<script src="client/main-es2015.835898518dc86985656d.js" type="module"></script>
		<script src="client/main-es5.835898518dc86985656d.js" nomodule defer></script>
	{{--angular scripts end--}}
@endsection
