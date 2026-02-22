$(document).ready(function () {
    let table = $('#mahasiswa').DataTable({
        bLengthChange: false,
        paging: false,
        scrollX: true,
        searching: true,
        ordering: false,
        info: false,
        autoWidth: false,
        language: {
            emptyTable: "Tidak ada data mahasiswa"
        },
        columnDefs: [
            { targets: [0, 3, 4, 5, 6, 7, 8], searchable: false }
        ]
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return text.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    async function loadJadwalPerHari() {
        const tahun_akademik = $('#tahun_akademik').val();
        if (!tahun_akademik) return alert('Pilih Tahun Akademik terlebih dahulu!');

        try {
            const res = await fetch(`/pembimbing-akademik?tahun_akademik=${tahun_akademik}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();

            table.clear();

            data.mahasiswa.forEach((mhs, i) => {
                const newRow = table.row.add([
                    i + 1,
                    escapeHtml(mhs.nim),
                    escapeHtml(mhs.nama_lengkap),
                    escapeHtml(mhs.StatusNama),
                    escapeHtml(mhs.ProdiNama),
                    escapeHtml(mhs.sesi),
                    escapeHtml(mhs.ip),
                    escapeHtml(mhs.ipk),
                    `<button class="btn btn-sm btn-info btnDetail" data-nim="${mhs.nim}" data-tahun="${tahun_akademik}">
                        <i class='bx bx-search-alt'></i> KRS
                    </button>`
                ]);

                const rowNode = newRow.node();
                if (mhs.ada_krs_menunggu) {
                    $(rowNode).addClass('table-warning');
                }
            });


            table.draw();

        } catch (err) {
            console.error(err);
            alert('Gagal memuat data mahasiswa');
        }
    }

    $('#btnFilter').on('click', loadJadwalPerHari);

    $('#jadwalContainer').on('click', '.btnDetail', async function () {
        const btn = $(this);
        const nim = btn.data('nim');
        const tahun = btn.data('tahun');
        const modalBody = $('#modalKRSBody');
        const modalHeader = $('#modalKRSHeader');
        modalBody.html('Loading...');
        modalHeader.html('Loading...');

        try {
            const res = await fetch(`/pembimbing-akademik/krs/${tahun}/${nim}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({})
            });

            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch {
                throw new Error('Server tidak mengembalikan JSON valid: ' + text);
            }

            if (!data.krs || data.krs.length === 0) {
                modalBody.html('<p>Belum ada KRS.</p>');
                modalHeader.html('Kartu Rencana Studi');
            } else {
                const first = data.krs[0];
                modalHeader.html(`
                            Kartu Rencana Studi <br>
                            Nama : ${escapeHtml(first.NamaMhsw ?? '-')} <br>
                            Nomor Pokok Mahasiswa : ${escapeHtml(first.nim ?? '-')} <br>
                            Program Studi : ${escapeHtml(first.NamaProdi ?? '-')} <br>
                        `);

                let html = '<table class="table table-bordered">';
                html +=
                    '<tr><th>Mata Kuliah</th><th>SKS</th><th>Nilai</th><th>Persetujuan PA</th></tr>';

                data.krs.forEach((krs, index) => {
                    const checkbox = krs.status_pa === 'MENUNGGU' ?
                        `<div class="form-check">
                                <input class="form-check-input cekPersetujuan" data-krsid="${krs.id}" type="checkbox" id="approve${index}" data-action="approve">
                                <label class="form-check-label" for="approve${index}">Setujui</label>
                            </div>` :
                        krs.status_pa === 'DISETUJUI' ?
                            `<div class="form-check">
                                <input class="form-check-input cekPersetujuan" data-krsid="${krs.id}" type="checkbox" id="batal${index}" checked data-action="batal">
                                <label class="form-check-label" for="batal${index}">Batal</label>
                            </div>` :
                            escapeHtml(krs.status_pa ?? '-');

                    html += `<tr>
                            <td>${escapeHtml(krs.NamaID)}</td>
                            <td>${escapeHtml(krs.sks)}</td>
                            <td>${escapeHtml(krs.nilai_angka ?? '-')}</td>
                            <td>${checkbox}</td>
                        </tr>`;
                });

                html += '</table>';
                modalBody.html(html);
            }

            new bootstrap.Modal(document.getElementById('modalKRS')).show();

        } catch (err) {
            console.error(err);
            modalBody.html(`<p style="color:red;">Error: ${escapeHtml(err.message)}</p>`);
        }
    });

    $('#modalKRS').on('hidden.bs.modal', loadJadwalPerHari);

    $('#modalKRSBody').on('change', '.cekPersetujuan', async function () {
        const cb = $(this);
        const krsId = cb.data('krsid');
        const action = cb.data('action');

        try {
            const res = await fetch('/pembimbing-akademik/krs/update-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    krs_id: krsId,
                    action
                })
            });

            const result = await res.json();

            console.log(result);


            if (result.success) {
                const label = cb.next('label');
                if (action === 'approve') {
                    cb.data('action', 'batal');
                    cb.prop('checked', true);
                    label.text('Batal');
                    Lobibox.notify('success', {
                        pauseDelayOnHover: true,
                        size: 'mini',
                        rounded: true,
                        icon: 'bx bx-check-circle',
                        delayIndicator: false,
                        continueDelayOnInactiveTab: false,
                        position: 'top right',
                        msg: 'Kartu Rencana Studi berhasil disetujui',
                        sound: false,
                    });
                } else {
                    cb.data('action', 'approve');
                    cb.prop('checked', false);
                    label.text('Setujui');
                    Lobibox.notify('success', {
                        pauseDelayOnHover: true,
                        size: 'mini',
                        rounded: true,
                        icon: 'bx bx-check-circle',
                        delayIndicator: false,
                        continueDelayOnInactiveTab: false,
                        position: 'top right',
                        msg: 'Kartu Rencana Studi berhasil dibatalkan',
                        sound: false,
                    });
                }
            } else alert('Gagal update status');

        } catch (err) {
            console.error(err);
            alert('Terjadi error saat update status');
        }
    });

});
