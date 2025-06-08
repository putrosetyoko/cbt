<aside class="main-sidebar">
    <section class="sidebar">
        <ul class="sidebar-menu" data-widget="tree">
            <li class="header">MAIN MENU</li>
            <?php 
            // Ambil segmen URL
            $page = $this->uri->segment(1);
            $page2 = $this->uri->segment(2);

            // Definisikan grup menu untuk kemudahan
            $master = ["tahunajaran", "jenjang", "kelas", "mapel", "guru", "siswa"];
            $penugasan = ["siswakelas", "penugasanguru", "pjsoal"]; 
            $users_settings = ["users", "settings"];

            // --- Logika Baru untuk Menu Ujian ---

            // 1. Kondisi untuk menu "Hasil Ujian" (Admin/Guru)
            $is_hasil_ujian_active = ($page === 'ujian' && ($page2 === 'hasil_ujian_siswa' || $page2 === 'detail_hasil_ujian'));

            // 2. Kondisi untuk menu "Ujian Saya" (Siswa)
            $is_ujian_siswa_active = ($page === 'ujian' && ($page2 === 'list_ujian_siswa' || $page2 === 'token' || $page2 === 'lembar_ujian'));

            // 3. Kondisi untuk "Kelola Ujian" (Prinsip Pengecualian)
            // Aktif jika di controller 'ujian', TAPI BUKAN halaman "Hasil Ujian" dan BUKAN halaman "Ujian Saya".
            $is_kelola_ujian_active = ($page === 'ujian' && !$is_hasil_ujian_active && !$is_ujian_siswa_active);
            
            // 4. Kondisi untuk parent "Manajemen Ujian"
            // Aktif jika di controller 'soal' ATAU jika di "Kelola Ujian".
            $is_manajemen_ujian_parent_active = ($page === 'soal' || $is_kelola_ujian_active);
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
                        <a href="<?=base_url('siswakelas')?>"><i class="fa fa-exchange"></i> Penempatan Kelas Siswa</a>
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

            <?php if( $this->ion_auth->is_admin() || $this->ion_auth->in_group('guru') ) : ?>
            <li class="treeview <?= $is_manajemen_ujian_parent_active ? "active menu-open" : "" ?>">
                <a href="#"><i class="fa fa-edit"></i> <span>Manajemen Ujian</span>
                    <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                </a>
                <ul class="treeview-menu">
                    <li class="<?=$page==='soal'?"active":""?>">
                        <a href="<?=base_url('soal')?>"><i class="fa fa-file-text-o"></i> Bank Soal</a>
                    </li>
                    <li class="<?= $is_kelola_ujian_active ? "active" : "" ?>"> 
                        <a href="<?=base_url('ujian')?>"><i class="fa fa-pencil-square-o"></i> Kelola Ujian</a>
                    </li>
                </ul>
            </li>

            <li class="header">LAPORAN</li> 
            <li class="<?= $is_hasil_ujian_active ? "active" : "" ?>">
                <a href="<?=base_url('ujian/hasil_ujian_siswa')?>"><i class="fa fa-bar-chart"></i> <span>Hasil Ujian</span></a>
            </li>
            <?php endif; ?>

            <?php if( $this->ion_auth->in_group('siswa') ) : ?> 
            <li class="<?= $is_ujian_siswa_active ? "active" : "" ?>"> 
                <a href="<?=base_url('ujian/list_ujian_siswa')?>"><i class="fa fa-edit"></i> <span>Ujian Saya</span></a> 
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