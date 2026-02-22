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
            { targets: [0, 1, 4, 5, 6], searchable: false }
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
            const res = await fetch(`/sidang-tugas-akhir?tahun_akademik=${tahun_akademik}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();

            table.clear();

            data.mahasiswa.forEach((mhs, i) => {
                const newRow = table.row.add([
                    i + 1,
                    escapeHtml(mhs.datetime_daftar_proposal),
                    escapeHtml(mhs.nim),
                    escapeHtml(mhs.nama_lengkap),
                    escapeHtml(mhs.judul_penelitian),
                    escapeHtml(mhs.status_proposal),
                    `<button class="btn btn-sm btn-info btnDetail" data-id="${mhs.id}">
                        <i class='bx bx-search-alt'></i> CEK
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
        const id = btn.data('id');
        const modalBody = $('#modalKRSBody');
        const modalHeader = $('#modalKRSHeader');
        modalBody.html('Loading...');
        modalHeader.html('Loading...');

        try {
            const res = await fetch(`/sidang-tugas-akhir/${id}`, {
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

            if (!data.proposal || data.proposal.length === 0) {
                modalBody.html('<p>Belum ada pengajuan.</p>');
                modalHeader.html('Pengajuan Proposal Tugas Akhir');
            } else {
                modalHeader.html(`
                            Pengajuan Proposal Tugas Akhir
                        `);

                let html = `<strong>Nama Mahasiswa : ${escapeHtml(data.proposal.NamaMhsw ?? '-')} </br>
                            Nomor Pokok Mahasiswa : ${escapeHtml(data.proposal.nim ?? '-')} </br>
                            Program Studi : ${escapeHtml(data.proposal.NamaProdi ?? '-')} </br></br>
                            Judul Tugas Akhir :</br> <code>${escapeHtml(data.proposal.judul_penelitian ?? '-')} </code></strong></br>`;
                const checkbox = data.proposal.status_proposal === 'MENUNGGU' ?
                    `<div class="col"><button type="button" class="btn btn-success btn-sm btn-flat cekPersetujuan" data-proposal="${data.proposal.id}" data-action="approve">Setujui Pendaftaran</button>
                    <button type="button" class="btn btn-danger btn-sm btn-flat cekPersetujuan" data-proposal="${data.proposal.id}" data-action="reject">Tolak Pendaftaran</button></div>` :
                    data.proposal.status_proposal === 'DISETUJUI' ?
                        `<div class="col"><button type="button" class="btn btn-danger btn-sm btn-flat cekPersetujuan" data-proposal="${data.proposal.id}" data-action="batal">Batalkan Persetujuan</button></div>` :
                        escapeHtml(data.proposal.status_proposal ?? '-');

                html += `${checkbox}`;
                modalBody.html(html);
            }

            new bootstrap.Modal(document.getElementById('modalKRS')).show();

        } catch (err) {
            console.error(err);
            modalBody.html(`<p style="color:red;">Error: ${escapeHtml(err.message)}</p>`);
        }
    });

    $('#modalKRS').on('hidden.bs.modal', loadJadwalPerHari);

    $('#modalKRSBody').on('click', '.cekPersetujuan', async function () {
        const cb = $(this);
        const id = cb.data('proposal');
        const action = cb.data('action');

        try {
            const res = await fetch('/sidang-tugas-akhir/tugas-akhir/update-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ krs_id: id, action })
            });

            const result = await res.json();

            if (result.success === true) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalKRS'));
                if (modal) modal.hide();
                loadJadwalPerHari();
            } else {
                alert('Gagal update status: ' + (result.message ?? 'Unknown error'));
            }

        } catch (err) {
            console.error(err);
            alert('Terjadi error saat update status');
        }
    });

});
