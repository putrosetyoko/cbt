<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= $subjudul ?></h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body">
        <ul class="alert alert-info" style="padding-left: 40px">
            <li>Silahkan import data dari excel, <b>WAJIB</> menggunakan format yang sudah disediakan</li>
            <li>Data tidak boleh ada yang kosong, harus terisi semua.</li>
        </ul>
        <div class="text-center">
            <a href="<?= base_url('uploads/import/format/siswa.xlsx') ?>" class="btn-default btn">Download Format</a>
        </div>
        <br>
        <div class="row">
            <?= form_open_multipart('siswa/preview'); ?>
            <label for="file" class="col-sm-offset-1 col-sm-3 text-right">Pilih File</label>
            <div class="col-sm-4">
                <div class="form-group">
                    <input type="file" name="upload_file">
                </div>
            </div>
            <div class="col-sm-3">
                <button name="preview" type="submit" class="btn btn-sm btn-success"><i class="fa fa-eye"></i> Preview</button>
            </div>
            <?= form_close(); ?>
            <div class="col-sm-6 col-sm-offset-3">
                <?php if (isset($_POST['preview'])) : ?>
                    <br>
                    <h4>Preview Data</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <td>No.</td>
                                <td>NISN</td>
                                <td>Nama</td>
                                <td>Jenis Kelamin</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $status = true;
                                if (empty($import)) {
                                    echo '<tr><td colspan="5" class="text-center">Data kosong! pastikan anda menggunakan format yang telah disediakan.</td></tr>'; // colspan adjusted
                                } else {
                                    $no = 1;
                                    foreach ($import as $data) :
                                        ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td class="<?= $data['nisn'] == null ? 'bg-danger' : ''; ?>">
                                                <?= $data['nisn'] == null ? 'BELUM DIISI' : $data['nisn']; ?>
                                            </td>
                                            <td class="<?= $data['nama'] == null ? 'bg-danger' : ''; ?>">
                                                <?= $data['nama'] == null ? 'BELUM DIISI' : $data['nama'];; ?>
                                            </td>
                                            <td class="<?= $data['jenis_kelamin'] == null ? 'bg-danger' : ''; ?>">
                                                <?= $data['jenis_kelamin'] == null ? 'BELUM DIISI' : $data['jenis_kelamin'];; ?>
                                            </td>
                                        </tr>
                                <?php
                                        if ($data['nisn'] == null || $data['nama'] == null || $data['jenis_kelamin'] == null) { // email removed
                                            $status = false;
                                        }
                                    endforeach;
                                }
                                ?>
                        </tbody>
                    </table>
                    <?php if ($status) : ?>

                        <?= form_open('siswa/do_import', null, ['data' => json_encode($import)]); ?>
                        <button type='submit' class='btn btn-block btn-flat bg-purple'>Import</button>
                        <?= form_close(); ?>

                    <?php endif; ?>
                    <br>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
