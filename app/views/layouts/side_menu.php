
		<!-- Sidebar -->
		<div class="sidebar" data-background-color="white">
			<div class="sidebar-logo">
				<!-- Logo Header -->
				<div class="logo-header" data-background-color="white">
					<a href="/" class="logo">
						<img src="/assets/img/kaiadmin/logo_dark.svg" alt="navbar brand" class="navbar-brand" height="20">
					</a>
					<div class="nav-toggle">
						<button class="btn btn-toggle toggle-sidebar">
							<i class="gg-menu-right"></i>
						</button>
						<button class="btn btn-toggle sidenav-toggler">
							<i class="gg-menu-left"></i>
						</button>
					</div>
					<button class="topbar-toggler more">
						<i class="gg-more-vertical-alt"></i>
					</button>
				</div>
				<!-- End Logo Header -->
			</div>
			<div class="sidebar-wrapper scrollbar scrollbar-inner">
				<div class="sidebar-content">
					<ul class="nav nav-secondary">

						<li class="nav-section">
							<span class="sidebar-mini-icon">
								<i class="fa fa-ellipsis-h"></i>
							</span>
							<h4 class="text-section">주문 관리</h4>
						</li>

						<li class="nav-item <?= (strpos($_SERVER['REQUEST_URI'], '/Order/addOrder') !== false) ? 'active' : '' ?>">
							<a href="/Order/addOrder">
								<i class="fas fa-plus-circle"></i>
								<p>주문 등록</p>
							</a>
						</li>

						<li class="nav-item <?= (strpos($_SERVER['REQUEST_URI'], '/Order/orderList') !== false) ? 'active' : '' ?>">
							<a href="/Order/orderList">
								<i class="fas fa-clipboard-list"></i>
								<p>주문 현황</p>
							</a>
						</li>

					</ul>
				</div>
			</div>
		</div>
		<!-- End Sidebar -->
