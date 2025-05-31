<aside class="main-sidebar">
    <section class="sidebar">
        <ul class="sidebar-menu" data-widget="tree">
            <li class="header">MAIN MENU</li>
            <?php 
            $page = $this->uri->segment(1);
            $page2 = $this->uri->segment(2);

            // Grup menu untuk kelas 'active'
            // Sesuaikan nama ini dengan nama controller Anda jika berbeda
            $master = ["tahunajaran", "jenjang", "kelas", "mapel", "guru", "siswa"];
            // Contoh jika nama controller lebih panjang:
            // $penugasan = ["siswakelasajaran", "gurumapelkelasajaran", "pjsoalajaran"]; 
            $penugasan = ["siswakelas", "penugasanguru", "pjsoal"]; // Ini sudah OK jika controller Anda namanya itu
            $manajemen_ujian = ["soal", "ujian"]; // Grup untuk Bank Soal dan Kelola Ujian
            $users_settings = ["users", "settings"];
            ?>
            <li class="<?= $page === 'dashboard' ? "active" : "" ?>">
                <a href="<?=base_url('dashboard')?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a>
            </li>

            <?php if($this->ion_auth->is_admin()) : ?>
            <li class="treeview <?= in_array($page, $master) ? "active menu-open" : "" ?>">
                <a href="#"><i class="fa fa-folder-open"></i> <span>Data Master</span>
                    <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                </a>
                <ul class="treeview-menu">
                    <li class="<?=$page==='tahunajaran'?"active":""?>"><a href="<?=base_url('tahunajaran')?>"><i class="fa fa-calendar-check-o"></i> Tahun Ajaran</a></li>
                    <li class="<?=$page==='jenjang'?"active":""?>"><a href="<?=base_url('jenjang')?>"><i class="fa fa-sitemap"></i> Jenjang Pendidikan</a></li> 
                    <li class="<?=$page==='kelas'?"active":""?>"><a href="<?=base_url('kelas')?>"><i class="fa fa-university"></i> Data Kelas</a></li>
                    <li class="<?=$page==='mapel'?"active":""?>"><a href="<?=base_url('mapel')?>"><i class="fa fa-book"></i> Mata Pelajaran</a></li>
                    <li class="<?=$page==='guru'?"active":""?>"><a href="<?=base_url('guru')?>"><i class="fa fa-user-secret"></i> Data Guru</a></li>
                    <li class="<?=$page==='siswa'?"active":""?>"><a href="<?=base_url('siswa')?>"><i class="fa fa-users"></i> Data Siswa</a></li>
                </ul>
            </li>
            <li class="treeview <?= in_array($page, $penugasan) ? "active menu-open" : "" ?>">
                <a href="#"><i class="fa fa-link"></i> <span>Penugasan & Relasi</span>
                    <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                </a>
                <ul class="treeview-menu">
                    <li class="<?=$page==='siswakelas'?"active":""?>"> 
                        <a href="<?=base_url('siswakelas')?>"><i class="fa fa-exchange"></i> Distribusi Kelas Siswa</a>
                    </li>
                    <li class="<?=$page==='penugasanguru'?"active":""?>"> 
                        <a href="<?=base_url('penugasanguru')?>"><i class="fa fa-briefcase"></i> Penugasan Guru</a>
                    </li>
                    <li class="<?=$page==='pjsoal'?"active":""?>"> 
                        <a href="<?=base_url('pjsoal')?>"><i class="fa fa-shield"></i> PJ Soal per Mapel</a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <?php 
            // Variabel untuk menentukan apakah grup menu "Manajemen Ujian" aktif
            // Grup ini aktif jika halaman saat ini adalah 'soal' ATAU ('ujian' dan sub-halamannya 'master')
            $is_manajemen_ujian_parent_active = ($page === 'soal' || ($page === 'ujian' && $page2 === 'master'));
            ?>
            <?php if( $this->ion_auth->is_admin() || $this->ion_auth->in_group('guru') ) : ?>
            <li class="treeview <?= $is_manajemen_ujian_parent_active ? "active menu-open" : "" ?>">
                <a href="#"><i class="fa fa-edit"></i> <span>Manajemen Ujian</span>
                    <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                </a>
                <ul class="treeview-menu">
                    <li class="<?=$page==='soal'?"active":""?>">
                        <a href="<?=base_url('soal')?>"><i class="fa fa-file-text-o"></i> Bank Soal</a>
                    </li>

                    <?php if( $this->ion_auth->in_group('guru') ) : ?> 
                    <li class="<?=$page==='ujian' && $page2 ==='master'?"active":""?>"> 
                        <a href="<?=base_url('ujian/master')?>"><i class="fa fa-pencil-square-o"></i> Kelola Ujian</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if( $this->ion_auth->in_group('siswa') ) : ?> 
            <li class="<?=$page==='ujian' && ($page2 ==='list' || empty($page2)) ?"active":""?>"> 
                <a href="<?=base_url('ujian/list')?>"><i class="fa fa-edit"></i> <span>Ujian Saya</span></a> 
            </li>
            <?php endif; ?>

            <?php if( !$this->ion_auth->in_group('siswa') ) : ?> 
            <li class="header">LAPORAN</li> 
            <li class="<?=$page==='hasilujian'?"active":""?>">
                <a href="<?=base_url('hasilujian')?>"><i class="fa fa-bar-chart"></i> <span>Hasil Ujian</span></a>
            </li>
            <?php endif; ?>

            <?php if($this->ion_auth->is_admin()) : ?>
            <li class="header">ADMINISTRATOR</li>
            <li class="treeview <?= in_array($page, $users_settings) ? "active menu-open" : "" ?>">
                <a href="#"><i class="fa fa-cogs"></i> <span>Pengaturan Sistem</span>
                    <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                </a>
                <ul class="treeview-menu">
                    <li class="<?=$page==='users'?"active":""?>">
                        <a href="<?=base_url('users')?>"><i class="fa fa-users"></i> Manajemen User</a>
                    </li>
                    <li class="<?=$page==='settings'?"active":""?>">
                        <a href="<?=base_url('settings')?>"><i class="fa fa-cog"></i> Settings Aplikasi</a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
    </section>
</aside>