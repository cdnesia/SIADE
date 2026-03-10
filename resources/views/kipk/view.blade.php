@extends('layouts.app')
@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h6 class="mb-0">Data IP Semester</h6>
            <div class="ms-auto">
                <a href="{{ route('kipk.index') }}" class="btn btn-sm btn-warning">Kembali</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center">
                    <thead class="thead-dark">
                        <tr>
                            <th>No</th>
                            <th>NIM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Program Studi</th>

                            <?php foreach ($tahun_akademik as $tahun): ?>
                            <th><?= $tahun ?></th>
                            <?php endforeach; ?>

                        </tr>
                    </thead>

                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($mahasiswa as $key=>$mhs): ?>
                        <?php
                        $row_merah = false;

                        foreach ($tahun_akademik as $thn) {
                            if (isset($row[$thn]) && $row[$thn] !== '-' && $row[$thn] < 3) {
                                $row_merah = true;
                                break;
                            }
                        }
                        ?>
                        <tr class="<?= $row_merah ? 'table-danger' : '' ?>">
                            <td><?= $no++ ?></td>
                            <td><?= $key ?></td>
                            <td class="text-left"><?= $mhs['nama_mahasiswa'] ?></td>
                            <td class="text-left"><?= $mhs['program_studi'] ?></td>

                            <?php foreach ($tahun_akademik as $tahun): ?>
                            <td>
                                <?= isset($mhs[$tahun]['ips']) ? number_format($mhs[$tahun]['ips'], 2) : '-' ?>
                            </td>
                            <?php endforeach; ?>

                        </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>

            </div>
        </div>
    </div>
@endsection
