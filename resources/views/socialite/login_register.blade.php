@extends('welcome')

@section('content')
<div class="container">
	<h1>Se connecter / S'enregistrer avec un compte social</h1>
	<p>
		
		<!-- Lien de redirection vers Facebook -->
		<a href="{{ route('socialite.redirect', 'facebook') }}" title="Connexion/Inscription avec Facebook" class="btn btn-link"  >Continuer avec Facebook</a>

	</p>
</div>
@endsection
