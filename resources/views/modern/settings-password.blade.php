@extends('components.layouts.app')

@php
    $pageTitle = 'Ubah Kata Sandi';
    $pageSubtitle = 'Perbarui kata sandi akun Anda';
@endphp

@pushOnce('scripts')
    <script>
        window.__MODERN_PAGE__ = 'password-index';
    </script>
@endpushOnce
