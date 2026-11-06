@extends('layouts.admin')

@section('title', 'Dashboard administrator')

@section('content')
    <div class="heading">
        <div><h1>Dashboard administrator</h1><p>Kelola katalog, pengguna, perjalanan, dan tarif.</p></div>
    </div>
    <div class="grid">
        @foreach (['destinations' => 'Destinasi', 'categories' => 'Kategori', 'transport_options' => 'Pilihan transportasi', 'transport_routes' => 'Trayek aktif', 'accommodations' => 'Penginapan', 'users' => 'Pengguna', 'trips' => 'Itinerary tersimpan'] as $key => $label)
            <div class="card"><small>{{ $label }}</small><strong>{{ number_format($stats[$key]) }}</strong></div>
        @endforeach
    </div>

    <section class="panel">
        <div class="heading" style="align-items:center;margin-bottom:12px"><h2 style="margin:0">Pengelolaan pengguna</h2><a class="button" href="{{ route('admin.users.create') }}">Tambah pengguna</a></div>
        <p class="note">Pendaftaran publik hanya membuat akun wisatawan. Akses admin dikelola di sini; administrator terakhir tidak dapat dicabut atau dihapus.</p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nama</th><th>Email</th><th>Bergabung</th><th>Peran</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->created_at->format('d M Y') }}</td>
                        <td><span class="badge">{{ $user->is_admin ? 'Administrator' : 'Wisatawan' }}</span></td>
                        <td><div class="actions">
                            <form method="POST" action="{{ route('admin.users.toggle-admin', $user) }}">@csrf @method('PATCH')<button class="small-btn" type="submit">{{ $user->is_admin ? 'Cabut admin' : 'Jadikan admin' }}</button></form>
                            <a class="small-btn" href="{{ route('admin.users.edit', $user) }}">Ubah</a>
                            @if (!auth()->user()->is($user))
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Hapus pengguna beserta itinerary tersimpan miliknya?')">@csrf @method('DELETE')<button class="small-btn danger" type="submit">Hapus</button></form>
                            @endif
                        </div></td>
                    </tr>
                @empty<tr><td colspan="5" class="muted">Belum ada pengguna.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $users->links() }}</div>
    </section>

    <section class="panel">
        <h2>Laporan perjalanan terbaru</h2>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Rencana</th><th>Pemilik</th><th>Durasi</th><th>Wisatawan</th><th>Total estimasi</th><th>Dibuat</th></tr></thead>
                <tbody>
                @forelse ($recentTrips as $trip)
                    <tr><td>{{ $trip->title }}</td><td>{{ $trip->user->name }}</td><td>{{ $trip->days }} hari</td><td>{{ $trip->travelers }}</td><td>Rp{{ number_format($trip->total_cost, 0, ',', '.') }}</td><td>{{ $trip->created_at->format('d M Y') }}</td></tr>
                @empty<tr><td colspan="6" class="muted">Belum ada perjalanan tersimpan.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
