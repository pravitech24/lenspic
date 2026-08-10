@extends('layouts.app')

@section('title', 'Complete your account')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-pink-50 px-4 py-10 flex items-center justify-center">
    <div class="w-full max-w-xl">
        <div class="text-center mb-8">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-pink-500 text-2xl text-white shadow-lg">
                ⚡
            </div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Complete your Kwikpic account</h1>
            <p class="mt-2 text-base text-slate-600">We’ll create your account for the {{ session('signup.role') === 'photographer' ? 'photographer' : 'user' }} experience.</p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/70 sm:p-8">
            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="role" value="{{ session('signup.role', 'user') }}">
                <div class="grid gap-4">
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">Full name</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Priya Sharma">
                    </div>
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="you@example.com">
                    </div>
                    <div>
                        <label for="phone" class="mb-1.5 block text-sm font-medium text-slate-700">Mobile number</label>
                        <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="+91 9876543210">
                    </div>
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                        <input id="password" type="password" name="password" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Min 8 characters">
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Confirm password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100" placeholder="Repeat password">
                    </div>
                </div>

                <button type="submit" class="mt-6 w-full rounded-2xl bg-indigo-600 px-4 py-3.5 text-base font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
                    Create account
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
