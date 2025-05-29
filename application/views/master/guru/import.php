<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= $subjudul ?></h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body">
        <?php if ($this->session->flashdata('error_message')) : ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h4><i class="icon fa fa-ban"></i> Error!</h4>
                <?= $this->session->flashdata('error_message'); ?>
            </div>
        <?php endif; ?>

        <ul class="alert alert-info" style="padding-left: 40px">
            <li>Silahkan import data dari excel, menggunakan format yang sudah disediakan</li>
            <li>Data tidak boleh ada yang kosong, harus terisi semua.</li>
        </ul>
        <div class="text-center">
            <a href="<?= base_url('uploads/import/format/guru.xlsx') ?>" class="btn-default btn">Download Format</a>
        </div>
        <br>
        <div class="row">
            <?= form_open_multipart('guru/preview'); ?>
            <label for="file" class="col-sm-offset-1 col-sm-3 text-right">Pilih File</label>
            <div class="col-sm-4">
                <div class="form-group">
                    <input type="file" name="upload_file" required> </div>
            </div>
            <div class="col-sm-3">
                <button name="preview" type="submit" class="btn btn-sm btn-success">Preview</button>
            </div>
            <?= form_close(); ?>
            <div class="col-sm-6 col-sm-offset-3">
                <?php if (isset($show_preview) && $show_preview) : ?>
                    <br>
                    <h4>Preview Data</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <td>No</td>
                                <td>NIP</td>
                                <td>Nama</td>
                                <td>Email</td>
                        </thead>
                        <tbody>
                            <?php
                                $status = true;
                                if (empty($import)) {
                                    echo '<tr><td colspan="5" class="text-center">Data kosong atau format file tidak valid! Pastikan anda menggunakan format yang telah disediakan.</td></tr>';
                                } else {
                                    $no = 1;
                                    foreach ($import as $data_row) :
                                        
                            ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td class="<?= $data_row['nip'] == null ? 'bg-danger' : ''; ?>">
                                                <?= $data_row['nip'] == null ? 'BELUM DIISI' : $data_row['nip']; ?>
                                            </td>
                                            <td class="<?= $data_row['nama_guru'] == null ? 'bg-danger' : ''; ?>">
                                                <?= $data_row['nama_guru'] == null ? 'BELUM DIISI' : $data_row['nama_guru']; ?>
                                            </td>
                                            <td class="<?= $data_row['email'] == null ? 'bg-danger' : ''; ?>">
                                                <?= $data_row['email'] == null ? 'BELUM DIISI' : $data_row['email']; ?>
                                            </td>
                                        </tr>
                            <?php
                                        if ($data_row['nip'] == null || $data_row['nama_guru'] == null || $data_row['email'] == null) {
                                            $status = false;
                                        }
                                    endforeach;
                                }
                            ?>
                        </tbody>
                    </table>
                    <?php if ($status && !empty($import)) : ?>

                        <?= form_open('guru/do_import', null, ['data' => json_encode($import)]); ?>
                        <button type='submit' class='btn btn-block btn-flat bg-purple'>Import</button>
                        <?= form_close(); ?>

                    <?php endif; ?>
                    <br>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

