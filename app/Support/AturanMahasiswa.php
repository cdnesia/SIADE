<?php

namespace App\Support;

/**
 * Aturan validasi data mahasiswa baru. Dipakai bersama oleh form Tambah Mahasiswa
 * dan Import Mahasiswa Baru supaya keduanya selalu konsisten.
 */
class AturanMahasiswa
{
    public static function rules(): array
    {
        return [
            'nama_mahasiswa' => 'required|string|max:200',
            'npm' => 'required|alpha_num|max:20|unique:master_mahasiswa,npm',
            'tahun_angkatan' => ['required', 'regex:/^\d{4}[12]$/'],
            'kode_program_studi' => 'required|exists:master_program_studi,kode_program_studi',
            'program_kuliah_id' => 'required|exists:master_kelas_perkuliahan,id',
            'jenis_pendaftaran_id' => 'required|exists:master_jenis_pendaftaran,id',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date|before:today',
            'nik' => 'nullable|digits_between:1,20|unique:master_mahasiswa,nik',
            'nisn' => 'nullable|string|max:20',
            'npsn' => 'nullable|string|max:20',
            'no_kipk' => 'nullable|string|max:25',
            'email' => 'nullable|email|max:150',
            'handphone' => 'nullable|string|max:30',
            'nama_ayah' => 'nullable|string|max:150',
            'nama_ibu_kandung' => 'nullable|string|max:150',
            'nama_wali' => 'nullable|string|max:150',
        ];
    }

    public static function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute maksimal :max karakter.',
            'email' => 'Format email tidak valid.',
            'date' => ':attribute bukan tanggal yang valid.',
            'before' => ':attribute harus sebelum hari ini.',
            'exists' => ':attribute yang dipilih tidak valid.',
            'in' => ':attribute yang dipilih tidak valid.',
            'alpha_num' => ':attribute hanya boleh huruf dan angka.',
            'digits_between' => ':attribute hanya boleh angka, maksimal :max digit.',
            'npm.unique' => 'NPM ini sudah terdaftar di data mahasiswa.',
            'nik.unique' => 'NIK ini sudah dipakai mahasiswa lain.',
            'tahun_angkatan.regex' => 'Format angkatan: tahun + semester, contoh 20261.',
        ];
    }

    public static function attributes(): array
    {
        return [
            'nama_mahasiswa' => 'Nama mahasiswa',
            'npm' => 'NPM',
            'tahun_angkatan' => 'Angkatan',
            'kode_program_studi' => 'Program studi',
            'program_kuliah_id' => 'Kelas perkuliahan',
            'jenis_pendaftaran_id' => 'Jenis pendaftaran',
            'nik' => 'NIK',
            'jenis_kelamin' => 'Jenis kelamin',
            'tempat_lahir' => 'Tempat lahir',
            'tanggal_lahir' => 'Tanggal lahir',
            'nisn' => 'NISN',
            'npsn' => 'NPSN',
            'no_kipk' => 'No. KIP Kuliah',
            'handphone' => 'No. handphone',
            'nama_ayah' => 'Nama ayah',
            'nama_ibu_kandung' => 'Nama ibu kandung',
            'nama_wali' => 'Nama wali',
        ];
    }
}
