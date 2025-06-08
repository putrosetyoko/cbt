<style>
/* Dalam file CSS Anda, misalnya assets/dist/css/AdminLTE.min.css atau custom.css */

/* Pastikan ikon eye overlay input bukan di dalamnya */
.form-group .form-control-feedback.toggle-password {
    position: absolute;
    right: 7px; /* Sesuaikan dengan padding input Anda */
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    width: 20px; /* Beri sedikit lebar agar mudah diklik */
    height: 20px; /* Beri sedikit tinggi agar mudah diklik */
    line-height: 20px; /* Pusatkan ikon secara vertikal jika perlu */
    text-align: center;
    pointer-events: auto; /* Sangat PENTING: Memastikan ikon bisa diklik */
    z-index: 2; /* Pastikan di atas input dan ikon lain */
}
/* Opsional: Sesuaikan padding kanan input jika ikon terlalu mepet */
.form-group input[name="password"] {
    padding-right: 40px !important; /* Beri ruang ekstra untuk ikon mata */
}
</style>

<div class="login-box pt-5">
  <div class="login-box-body">
    <h3 class="text-center mt-0 mb-4">
      <b>Ujian Online CBT</b>
    </h3>
    <p class="login-box-msg">Log In Untuk Memulai Sesi</p>

    <div id="infoMessage" class="text-center"><?php echo $message;?></div>

    <?= form_open("auth/cek_login", array('id'=>'login'));?>
      <div class="form-group has-feedback">
        <?= form_input($identity);?>
        <span class="fa fa-envelope-o form-control-feedback"></span>
        <span class="help-block"></span>
      </div>
      <div class="form-group has-feedback">
        <?= form_input($password);?>
        <span class="fa fa-eye-slash form-control-feedback toggle-password"></span>
        <span class="help-block"></span>
      </div>
      <div class="row">
        <div class="col-xs-12">
          <?= form_submit('submit', lang('login_submit_btn'), array('id'=>'submit','class'=>'btn btn-primary btn-block btn-flat'));?>
        </div>
      </div>
    <?= form_close(); ?>
  </div>
</div>

<script type="text/javascript">
  let base_url = '<?=base_url();?>';
</script>
<script src="<?=base_url()?>assets/dist/js/app/auth/login.js"></script>