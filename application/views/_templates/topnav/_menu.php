<nav class="navbar navbar-static-top bg-purple">
	<div class="container bg-purple">
		<div class="navbar-header ">
			<a href="<?=base_url()?>" class="navbar-brand"><i class="fa fa-laptop"></i> <b>CBT Online</b></a>
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse">
				<i class="fa fa-bars"></i>
			</button>
		</div>

		<!-- Collect the nav links, forms, and other content for toggling -->
		<div class="collapse navbar-collapse pull-left" id="navbar-collapse">
			<ul class="nav navbar-nav">
				<li><a href="#">SMP Islamic Center Samarinda | Shaleh • Muslih • Berprestasi</a></li>
			</ul>
		</div>
		<div class="navbar-custom-menu">
			<ul class="nav navbar-nav">
			<li class="dropdown user user-menu">
                <!-- Menu Toggle Button -->
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <!-- The user image in the navbar-->
                    <!-- hidden-xs hides the username on small devices so only the image appears. -->
                    <img src="<?=base_url()?>assets/dist/img/user1.png" class="user-image" alt="User Image">
                    <span class="hidden-xs"><?=$user->first_name.' '.$user->last_name?></span>
                </a>
                <!-- <ul class="dropdown-menu">
                    The user image in the menu
                    <li class="user-header">
                        <img src="<?=base_url()?>assets/dist/img/user1.png" class="img-circle" alt="User Image">
                        <p>
                            <?=$user->first_name.' '.$user->last_name?>
                            <small>Anggota sejak <?=date('M, Y', $user->created_on)?></small>
                        </p>
                    </li>
                    Menu Body
                    <li class="user-footer">
                        <div class="pull-left">
                            <a href="<?=base_url()?>" class="btn btn-default btn-flat">Dashboard</a>
                        </div>
                        <div class="pull-right">
                            <a href="#" id="logout" class="btn btn-default btn-flat">Logout</a>
                        </div>
                    </li>
                </ul> -->
            </li>
			</ul>
    </div>
		<!-- /.navbar-collapse -->
	</div>
	<!-- /.container-fluid -->
</nav>
