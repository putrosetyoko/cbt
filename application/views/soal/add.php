<?php
$id_mapel_pj = '';
$nama_mapel_pj = '';
if (isset($mapel_pj) && $mapel_pj) { // Jika yang login adalah Guru PJ Soal
    $id_mapel_pj = $mapel_pj->id_mapel;
    $nama_mapel_pj = $mapel_pj->nama_mapel;
}
?>
<div class="row">
    <div class="col-sm-12">
        <?=form_open_multipart('soal/save', array('id'=>'formSoal'), array('method'=>'add'));?>
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"><?= htmlspecialchars($subjudul ?? 'Buat Soal Baru'); ?></h3>
                <div class="box-tools pull-right">
                    <a href="<?=base_url('soal')?>" class="btn btn-sm btn-warning btn-flat"><i class="fa fa-arrow-left"></i> Batal</a>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-8 col-sm-offset-2">
                        <div class="form-group">
                            <label for="mapel_id_form">Mata Pelajaran <span class="text-danger">*</span></label>
                            <?php if ($is_admin) : ?>
                                <select name="mapel_id" id="mapel_id_form" class="form-control select2" style="width:100% !important" required>
                                    <option value="">-- Pilih Mata Pelajaran --</option>
                                    <?php if(isset($all_mapel)): foreach ($all_mapel as $m) : ?>
                                        <option value="<?=$m->id_mapel?>"><?=htmlspecialchars($m->nama_mapel)?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            <?php else : // Guru PJ Soal ?>
                                <input type="hidden" name="mapel_id" value="<?= $id_mapel_pj; ?>">
                                <input type="text" readonly class="form-control" value="<?= htmlspecialchars($nama_mapel_pj); ?>">
                            <?php endif; ?>
                            <small class="help-block text-danger" id="error_mapel_id"></small>
                        </div>

                        <div class="form-group">
                            <label for="id_jenjang_form">Jenjang <span class="text-danger">*</span></label>
                            <select name="id_jenjang" id="id_jenjang_form" class="form-control select2" style="width:100% !important" required>
                                <option value="">-- Pilih Jenjang --</option>
                                <?php if(isset($all_jenjang)): foreach ($all_jenjang as $j) : ?>
                                    <option value="<?=$j->id_jenjang?>"><?=htmlspecialchars($j->nama_jenjang)?></option>
                                <?php endforeach; endif; ?>
                            </select>
                            <small class="help-block text-danger" id="error_id_jenjang"></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="soal_text_form">Isi Soal <span class="text-danger">*</span></label>
                            <textarea name="soal" id="soal_text_form" class="form-control summernote" placeholder="Ketik isi soal di sini..."></textarea>
                            <small class="help-block text-danger" id="error_soal"></small>
                        </div>

                        <div class="form-group">
                            <label for="file_soal_form">File Pendukung Soal (Gambar/Audio)</label>
                            <input type="file" name="file_soal" id="file_soal_form" class="form-control">
                            <small class="help-block">Kosongkan jika tidak ada. Tipe: jpg, png, mp3, mp4. Max: 2MB.</small>
                            <small class="help-block text-danger" id="error_file_soal"></small>
                        </div>
                        <hr>

                        <?php $abjad = ['a', 'b', 'c', 'd', 'e']; foreach ($abjad as $abj) : $ABJ = strtoupper($abj); ?>
                        <div class="form-group">
                            <label for="jawaban_<?= $abj; ?>_form">Opsi Jawaban <?= $ABJ; ?> <span class="text-danger">*</span></label>
                            <textarea name="jawaban_<?= $abj; ?>" id="jawaban_<?= $abj; ?>_form" class="form-control summernote_opsi" placeholder="Isi opsi <?= $ABJ; ?>..."></textarea>
                            <small class="help-block text-danger" id="error_jawaban_<?= $abj; ?>"></small>
                        </div>
                        <div class="form-group">
                            <label for="file_<?= $abj; ?>_form">File Pendukung Opsi <?= $ABJ; ?> (Gambar/Audio)</label>
                            <input type="file" name="file_<?= $abj; ?>" id="file_<?= $abj; ?>_form" class="form-control">
                            <small class="help-block">Kosongkan jika tidak ada.</small>
                            <small class="help-block text-danger" id="error_file_<?= $abj; ?>"></small>
                        </div>
                        <hr>
                        <?php endforeach; ?>

                        <div class="form-group">
                            <label for="jawaban_kunci_form">Kunci Jawaban <span class="text-danger">*</span></label>
                            <select required="required" name="jawaban" id="jawaban_kunci_form" class="form-control select2" style="width:100%!important">
                                <option value="" disabled selected>-- Pilih Kunci Jawaban --</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                            </select>
                            <small class="help-block text-danger" id="error_jawaban"></small>
                        </div>

                        <div class="form-group">
                            <label for="bobot_form">Bobot Soal <span class="text-danger">*</span></label>
                            <input required="required" value="1" type="number" name="bobot" placeholder="Bobot Soal" id="bobot_form" class="form-control" min="1">
                            <small class="help-block text-danger" id="error_bobot"></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <div class="col-sm-8 col-sm-offset-2 text-right">
                    <a href="<?=base_url('soal')?>" class="btn btn-flat btn-warning"><i class="fa fa-arrow-left"></i> Batal</a>
                    <button type="submit" id="submitBtnSoal" class="btn btn-flat bg-purple"><i class="fa fa-save"></i> Simpan Soal</button>
                </div>
            </div>
        </div>
        <?=form_close();?>
    </div>
</div>

<script src="<?=base_url()?>assets/plugins/summernote/summernote-bs4.min.js"></script>
<script src="<?=base_url()?>assets/dist/js/app/soal/add.js"></script>
<script>
$(document).ready(function() {
    if($.fn.select2){ $('.select2').select2({ placeholder: "-- Pilih --", allowClear: true }); }
    if($.fn.summernote) { 
        $('.summernote').summernote({
            height: 150,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']], // Hapus 'picture' jika upload file terpisah
                ['view', ['fullscreen', 'codeview', 'help']],
            ],
            callbacks: {
                // Jika ingin menangani paste sebagai plain text
                onPaste: function (e) {
                    var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
                    e.preventDefault();
                    document.execCommand('insertText', false, bufferText);
                }
            }
        });
        $('.summernote_opsi').summernote({
            height: 75,
            toolbar: [
                ['font', ['bold', 'underline']],
                ['insert', ['link']],
            ],
            callbacks: {
                onPaste: function (e) {
                    var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
                    e.preventDefault();
                    document.execCommand('insertText', false, bufferText);
                }
            }
        });
    }
});
</script>
