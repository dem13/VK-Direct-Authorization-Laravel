@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    <div class="user-list">
                        @foreach($conversations as $conversation)
                                <div class="user-list-item">
                                    <img class="user-list-item-img" src="{{ $conversation['photo'] }}" alt="List user">
                                    <div class="user-list-item-text">
                                        <h5><a href="#">{{ $conversation['name'] }}</a></h4>
                                        <h6 class="text-truncate" >{{ $conversation['last_message']['text'] }}</h3>
                                        <p>{{ gmdate("Y-m-d H:i:s ", $conversation['last_message']['date']) }}</p>
                                    </div>
                                </div>
                        @endforeach
                  </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
