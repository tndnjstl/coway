
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
						<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
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

						<!-- 영업관리 (전체 공통) -->
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
						<li class="nav-item <?= (strpos($_SERVER['REQUEST_URI'], '/Customer/list') !== false) ? 'active' : '' ?>">
							<a href="/Customer/list">
								<i class="fas fa-users"></i>
								<p>고객 관리</p>
							</a>
						</li>
						<li class="nav-item <?= (strpos($_SERVER['REQUEST_URI'], '/Schedule/index') !== false) ? 'active' : '' ?>">
							<a href="/Schedule/index">
								<i class="fas fa-calendar-alt"></i>
								<p>일정 관리</p>
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

						<?php if (isset($_SESSION['info']) && is_manager()): ?>
						<!-- 영업운영관리 (manager, admin) -->
						<li class="nav-section">
							<span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
							<h4 class="text-section">영업운영관리</h4>
						</li>
						<li class="nav-item <?= (strpos($_SERVER['REQUEST_URI'], '/Manage/orderList') !== false) ? 'active' : '' ?>">
							<a href="/Manage/orderList">
								<i class="fas fa-list-alt"></i>
								<p>팀 주문 현황</p>
							</a>
						</li>
						<li class="nav-item <?= (strpos($_SERVER['REQUEST_URI'], '/Manage/performance') !== false) ? 'active' : '' ?>">
							<a href="/Manage/performance">
								<i class="fas fa-chart-bar"></i>
								<p>영업자 실적</p>
							</a>
						</li>
						<li class="nav-item <?= (strpos($_SERVER['REQUEST_URI'], '/Manage/approval') !== false) ? 'active' : '' ?>">
							<a href="/Manage/approval">
								<i class="fas fa-check-circle"></i>
								<p>주문 승인 관리</p>
							</a>
						</li>
						<?php endif; ?>

						<?php if (isset($_SESSION['info']) && is_admin_role()): ?>
						<!-- 관리기능 (admin) -->
						<li class="nav-section">
							<span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
							<h4 class="text-section">관리기능</h4>
						</li>
						<li class="nav-item <?= (strpos($_SERVER['REQUEST_URI'], '/Admin/memberList') !== false) ? 'active' : '' ?>">
							<a href="/Admin/memberList">
								<i class="fas fa-user-cog"></i>
								<p>사용자 관리</p>
							</a>
						</li>
						<li class="nav-item <?= (strpos($_SERVER['REQUEST_URI'], '/Admin/productList') !== false) ? 'active' : '' ?>">
							<a href="/Admin/productList">
								<i class="fas fa-box"></i>
								<p>제품 관리</p>
							</a>
						</li>
						<li class="nav-item <?= (strpos($_SERVER['REQUEST_URI'], '/Admin/codeList') !== false) ? 'active' : '' ?>">
							<a href="/Admin/codeList">
								<i class="fas fa-code"></i>
								<p>공통코드 관리</p>
							</a>
						</li>
						<?php endif; ?>

					</ul>
				</div>
			</div>
		</div>
		<!-- End Sidebar -->
