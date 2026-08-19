@extends('layouts.error-page')

@section('codigo', '419')
@section('icono', 'fa-solid fa-clock-rotate-left')
@section('titulo', 'Tu sesión expiró')
@section('mensaje', 'Por seguridad, cerramos tu sesión por inactividad. Iniciá sesión de nuevo para continuar.')
@section('boton_texto', 'Iniciar sesión')
@section('boton_url', route('login'))
