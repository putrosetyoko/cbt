<aside class="main-sidebar">

    <section class="sidebar">

        <ul class="sidebar-menu" data-widget="tree">
            <li class="header">MAIN MENU</li>
            <?php 
            $page = $this->uri->segment(1); // Halaman saat ini
            $page2 = $this->uri->segment(2); // Sub-halaman jika ada (misal: ujian/master)

            // Definisikan grup menu untuk kelas 'active'
            $master = ["tahunajaran", "jenjang", "kelas", "mapel", "guru", "siswa"];
            $penugasan = ["siswakelas", "penugasanguru", "pjsoal"]; // Menu baru untuk relasi/penugasan
            $users = ["users"];
            ?>
            <li class="<?= $page === 'dashboard' ? "active" : "" ?>">
                <a href="<?=base_url('dashboard')?>"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a>
            </li>

            <?php if($this->ion_auth->is_admin()) : ?>
            <li class="treeview <?= in_array($page, $master) ? "active menu-open" : "" ?>">
                <a href="#"><i class="fa fa-folder"></i> <span>Data Master</span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li class="<?=$page==='tahunajaran'?"active":""?>">
                        <a href="<?=base_url('tahunajaran')?>">
                            <i class="fa fa-calendar-check-o"></i> Tahun Ajaran
                        </a>
                    </li>
                    <li class="<?=$page==='jenjang'?"active":""?>">
                        <a href="<?=base_url('jenjang')?>">
                            <i class="fa fa-signal"></i> Jenjang
                        </a>
                    </li>
                    <li class="<?=$page==='kelas'?"active":""?>">
                        <a href="<?=base_url('kelas')?>">
                            <i class="fa fa-university"></i> Kelas
                        </a>
                    </li>
                    <li class="<?=$page==='mapel'?"active":""?>">
                        <a href="<?=base_url('mapel')?>">
                            <i class="fa fa-book"></i> Mata Pelajaran
                        </a>
                    </li>
                    <li class="<?=$page==='guru'?"active":""?>">
                        <a href="<?=base_url('guru')?>">
                            <i class="fa fa-user-secret"></i> Guru
                        </a>
                    </li>
                    <li class="<?=$page==='siswa'?"active":""?>"> 
                        <a href="<?=base_url('siswa')?>"> 
                            <i class="fa fa-users"></i> Siswa 
                        </a>
                    </li>
                </ul>
            </li>
            <li class="treeview <?= in_array($page, $penugasan) ? "active menu-open" : "" ?>">
                <a href="#"><i class="fa fa-link"></i> <span>Penugasan & Relasi</span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li class="<?=$page==='siswakelas'?"active":""?>">
                        <a href="<?=base_url('siswakelas')?>">
                            <i class="fa fa-exchange"></i> Siswa - Kelas - TA
                        </a>
                    </li>
                    <li class="<?=$page==='penugasanguru'?"active":""?>">
                        <a href="<?=base_url('penugasanguru')?>">
                            <i class="fa fa-sitemap"></i> Guru - Mapel - Kelas
                        </a>
                    </li>
                    <li class="<?=$page==='pjsoal'?"active":""?>">
                        <a href="<?=base_url('pjsoal')?>">
                            <i class="fa fa-shield"></i> PJ Soal
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if( $this->ion_auth->is_admin() || $this->ion_auth->in_group('guru') ) : ?>
            <li class="<?=$page==='soal'?"active":""?>">
                <a href="<?=base_url('soal')?>" rel="noopener noreferrer">
                    <i class="fa fa-file-text-o"></i> <span>Bank Soal</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if( $this->ion_auth->in_group('guru') ) : ?>
            <li class="<?=$page==='ujian' && $page2 ==='master'?"active":""?>"> <a href="<?=base_url('ujian/master')?>" rel="noopener noreferrer">
                    <i class="fa fa-pencil-square-o"></i> <span>Kelola Ujian</span> </a>
            </li>
            <?php endif; ?>

            <?php if( $this->ion_auth->in_group('siswa') ) : ?> 
            <li class="<?=$page==='ujian' && ($page2 ==='list' || empty($page2)) ?"active":""?>"> <a href="<?=base_url('ujian/list')?>" rel="noopener noreferrer">
                    <i class="fa fa-edit"></i> <span>Ujian</span> </a>
            </li>
            <?php endif; ?>

            <?php if( !$this->ion_auth->in_group('siswa') ) : ?> 
            <li class="header">REPORTS</li>
            <li class="<?=$page==='hasilujian'?"active":""?>">
                <a href="<?=base_url('hasilujian')?>" rel="noopener noreferrer">
                    <i class="fa fa-bar-chart"></i> <span>Hasil Ujian</span> </a>
            </li>
            <?php endif; ?>

            <?php if($this->ion_auth->is_admin()) : ?>
            <li class="header">ADMINISTRATOR</li>
            <li class="<?=$page==='users'?"active":""?>">
                <a href="<?=base_url('users')?>" rel="noopener noreferrer">
                    <i class="fa fa-users"></i> <span>Manajemen User</span>
                </a>
            </li>
            <li class="<?=$page==='settings'?"active":""?>">
                <a href="<?=base_url('settings')?>" rel="noopener noreferrer">
                    <i class="fa fa-cog"></i> <span>Settings</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>

    </section>
</aside>