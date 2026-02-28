
		<!-- Sidebar -->
		<div class="sidebar" data-background-color="white">
			<div class="sidebar-logo">
				<!-- Logo Header -->
				<div class="logo-header" data-background-color="white">
					<a href="/" class="logo text-decoration-none d-flex align-items-center gap-1">
						<span class="fw-bold text-primary" style="font-size:17px;letter-spacing:-0.5px;">COWAY</span>
						<small class="text-muted" style="font-size:11px;">영업관리</small>
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

						<!-- 요약 -->
						<li class="nav-section">
							<span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
							<h4 class="text-section">요약</h4>
						</li>
						<li class="nav-item <?= ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '') ? 'active' : '' ?>">
							<a href="/">
								<i class="fas fa-home"></i>
								<p>홈</p>
							</a>
						</li>

						<!-- 영업관리 -->
						<li class="nav-section">
							<span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
							<h4 class="text-section">영업관리</h4>
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

						<!-- 보고서 -->
						<li class="nav-section">
							<span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
							<h4 class="text-section">보고서</h4>
						</li>
						<li class="nav-item <?= (strpos($_SERVER['REQUEST_URI'], '/Order/prospectReport') !== false) ? 'active' : '' ?>">
							<a href="/Order/prospectReport">
								<i class="fas fa-file-alt"></i>
								<p>가망고객 보고서</p>
							</a>
						</li>

					</ul>
				</div>
			</div>
		</div>
		<!-- End Sidebar -->
