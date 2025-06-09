<?php
// Default values
$id_mapel_soal = $soal->mapel_id ?? '';
$nama_mapel_soal = $soal->nama_mapel ?? ''; // Diasumsikan $soal sudah di-join dengan mapel dan jenjang
$id_jenjang_soal = $soal->id_jenjang ?? '';

// Jika yang login adalah Guru PJ Soal, mapelnya sudah pasti
if (isset($is_guru) && $is_guru && isset($pj_mapel_data) && $pj_mapel_data) {
    $id_mapel_soal = $pj_mapel_data->id_mapel;
    $nama_mapel_soal = $pj_mapel_data->nama_mapel;
}
?>

<style>
    /* Untuk konten yang diedit di dalam Summernote */
.note-editable {
    font-family: Helvetica, sans-serif !important; /* Gunakan Helvetica atau font default sistem */
    font-size: 14px !important;
    line-height: 1.5; /* Jarak antar baris */
    text-align: left !important; /* Pastikan rata kiri */
    /* Menghilangkan margin default untuk paragraf yang bisa muncul dari paste */
    padding: 10px; /* Padding di dalam editor */
}

/* Untuk paragraf di dalam editor */
.note-editable p {
    margin: 0 !important;
    padding: 0 !important;
    text-indent: 0 !important;
}

/* Untuk elemen div yang mungkin ditempel dari Word */
.note-editable div {
    margin: 0 !important;
    padding: 0 !important;
    text-indent: 0 !important;
}
</style>

<div class="row">
    <div class="col-sm-12">
        <?=form_open_multipart('soal/save', array('id'=>'formSoalEdit'), array('method'=>'edit', 'id_soal' => $soal->id_soal));?>
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"><?= htmlspecialchars($subjudul ?? 'Edit Soal'); ?></h3>
                <div class="box-tools pull-right">
                    <a href="<?=base_url('soal')?>" class="btn btn-sm btn-warning btn-flat"><i class="fa fa-arrow-left"></i> Kembali ke Bank Soal</a>
                    <!-- <button type="submit" id="submitBtnSoalEdit" class="btn btn-sm btn-flat bg-purple"><i class="fa fa-save"></i> Simpan</button> -->
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-10 col-sm-offset-1">
                        <!-- <div class="well well-sm">
                            <strong>Mapel Asal:</strong> <?= htmlspecialchars($soal->nama_mapel ?? 'N/A'); ?><br>
                            <strong>Jenjang Asal:</strong> <?= htmlspecialchars($soal->nama_jenjang ?? 'N/A'); ?><br>
                            <strong>Pembuat Asal:</strong> <?= htmlspecialchars($soal->nama_pembuat ?? 'N/A'); ?>
                        </div> -->

                        <div class="form-group">
                            <label for="mapel_id_form_edit">Mata Pelajaran <span class="text-danger">*</span></label>
                            <?php if ($is_admin) : ?>
                                <select name="mapel_id" id="mapel_id_form_edit" class="form-control select2" style="width:100% !important" required>
                                    <option value="">-- Pilih Mata Pelajaran --</option>
                                    <?php if(isset($all_mapel)): foreach ($all_mapel as $m) : ?>
                                        <option value="<?=$m->id_mapel?>" <?= $m->id_mapel == $id_mapel_soal ? "selected" : "" ?>><?=htmlspecialchars($m->nama_mapel)?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            <?php else : // Guru PJ Soal, mapel tidak bisa diubah ?>
                                <input type="hidden" name="mapel_id" value="<?= $id_mapel_soal; ?>">
                                <input type="text" readonly class="form-control" value="<?= htmlspecialchars($nama_mapel_soal); ?>">
                            <?php endif; ?>
                            <small class="help-block text-danger" id="error_mapel_id"></small>
                        </div>

                        <div class="form-group">
                            <label for="id_jenjang_form_edit">Jenjang <span class="text-danger">*</span></label>
                            <select name="id_jenjang" id="id_jenjang_form_edit" class="form-control select2" style="width:100% !important" required>
                                <option value="">-- Pilih Jenjang --</option>
                                <?php if(isset($all_jenjang)): foreach ($all_jenjang as $j) : ?>
                                    <option value="<?=$j->id_jenjang?>" <?= $j->id_jenjang == $id_jenjang_soal ? "selected" : "" ?>><?=htmlspecialchars($j->nama_jenjang)?></option>
                                <?php endforeach; endif; ?>
                            </select>
                            <small class="help-block text-danger" id="error_id_jenjang"></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="soal_text_form_edit">Isi Soal <span class="text-danger">*</span></label>
                            <textarea name="soal" id="soal_text_form_edit" class="form-control summernote"><?= htmlspecialchars_decode($soal->soal ?? ''); ?></textarea>
                            <small class="help-block text-danger" id="error_soal"></small>
                        </div>

                        <div class="form-group">
                            <label for="file_soal_form_edit">File Pendukung Soal (Gambar/Video/Audio)</label>
                            <input type="file" name="file_soal" id="file_soal_form_edit" class="form-control">
                            <small class="help-block">Kosongkan jika tidak ingin mengubah. Tipe: jpg, png, mp3, mp4. Max: 2MB.</small>
                            <?php if (!empty($soal->file)) : ?>
                                <div class="mt-2">
                                    File saat ini: <a href="<?= base_url('uploads/bank_soal/'.$soal->file) ?>" target="_blank"><?= $soal->file ?></a>
                                    <label style="margin-left: 20px;"><input type="checkbox" name="hapus_file_soal" value="1"> Hapus file ini</label>
                                </div>
                            <?php endif; ?>
                            <small class="help-block text-danger" id="error_file_soal"></small>
                        </div>
                        <hr>

                        <?php $abjad = ['a', 'b', 'c', 'd', 'e']; foreach ($abjad as $abj) : $ABJ = strtoupper($abj); 
                            $opsi_field = 'opsi_'.$abj;
                            $file_opsi_field = 'file_'.$abj;
                        ?>
                        <div class="form-group">
                            <label for="jawaban_<?= $abj; ?>_form_edit">Opsi Jawaban <?= $ABJ; ?> <span class="text-danger">*</span></label>
                            <textarea name="jawaban_<?= $abj; ?>" id="jawaban_<?= $abj; ?>_form_edit" class="form-control summernote"><?= htmlspecialchars_decode($soal->$opsi_field ?? ''); ?></textarea>
                            <small class="help-block text-danger" id="error_jawaban_<?= $abj; ?>"></small>
                        </div>
                        <div class="form-group">
                            <label for="file_<?= $abj; ?>_form_edit">File Pendukung Opsi <?= $ABJ; ?></label>
                            <input type="file" name="file_<?= $abj; ?>" id="file_<?= $abj; ?>_form_edit" class="form-control">
                            <small class="help-block">Biarkan jika tidak ingin mengubah apapun.</small>
                            <?php if (!empty($soal->$file_opsi_field)) : ?>
                                <div class="mt-2">
                                    File Opsi <?= $ABJ ?> saat ini: <a href="<?= base_url('uploads/bank_soal/'.$soal->$file_opsi_field) ?>" target="_blank"><?= $soal->$file_opsi_field ?></a>
                                    <label style="margin-left: 20px;"><input type="checkbox" name="hapus_file_<?= $abj ?>" value="1"> Hapus file opsi <?= $ABJ ?></label>
                                </div>
                            <?php endif; ?>
                            <small class="help-block text-danger" id="error_file_<?= $abj; ?>"></small>
                        </div>
                        <hr>
                        <?php endforeach; ?>

                        <div class="form-group">
                            <label for="jawaban_kunci_form_edit">Kunci Jawaban <span class="text-danger">*</span></label>
                            <select required="required" name="jawaban" id="jawaban_kunci_form_edit" class="form-control select2" style="width:100%!important">
                                <option value="">-- Pilih Kunci Jawaban --</option>
                                <?php foreach ($abjad as $abj_option) : $ABJ_OPTION = strtoupper($abj_option); ?>
                                <option value="<?= $ABJ_OPTION ?>" <?= ($soal->jawaban ?? '') == $ABJ_OPTION ? "selected" : "" ?>><?= $ABJ_OPTION ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="help-block text-danger" id="error_jawaban"></small>
                        </div>

                        <div class="form-group">
                            <label for="bobot_form_edit">Bobot Soal <span class="text-danger">*</span></label>
                            <input required="required" value="<?= $soal->bobot ?? '1'; ?>" type="number" name="bobot" placeholder="Bobot Soal" id="bobot_form_edit" class="form-control" min="1">
                            <small class="help-block text-danger" id="error_bobot"></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <div class="col-sm-10 col-sm-offset-1 text-right">
                    <a href="<?=base_url('soal')?>" class="btn btn-flat btn-warning"><i class="fa fa-arrow-left"></i> Batal</a>
                    <button type="submit" id="submitBtnSoalEdit" class="btn btn-flat bg-purple"><i class="fa fa-save"></i> Simpan</button>
                </div>
            </div>
        </div>
        <?=form_close();?>
    </div>
</div>

<script src="<?=base_url()?>assets/dist/js/app/soal/edit.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2
    if($.fn.select2){ 
        $('.select2').select2({ 
            placeholder: "-- Pilih --", 
            allowClear: true 
        }); 
    }

    // Initialize Summernote for both questions and answers
    if($.fn.summernote) { 
        // Configuration for main question
        $('.summernote').summernote({
            placeholder: 'Tulis soal di sini...',
            tabsize: 2,
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['paragraph', ['paragraph', 'left', 'center', 'right', 'justify']],
                ['view', ['fullscreen', 'codeview']]
            ],
            callbacks: {
                onInit: function() {
                    console.log('Summernote main question initialized');
                }
            }
        });
    } else {
        console.error('Summernote plugin not loaded');
    }
});
</script>
