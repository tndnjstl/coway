		<div class="main-header">
			<div class="main-header-logo">
				<!-- Logo Header -->
				<div class="logo-header" data-background-color="white">
					<a href="/" class="logo text-decoration-none d-flex align-items-center gap-1">
						<span class="fw-bold text-primary" style="font-size:18px;letter-spacing:-0.5px;">COWAY</span>
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

			<!-- Navbar Header -->
			<nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
				<div class="container-fluid">
					<ul class="navbar-nav topbar-nav ms-md-auto align-items-center gap-2">
						<?php if (isset($_SESSION['is_login']) && $_SESSION['is_login']): ?>
						<li class="nav-item d-flex align-items-center gap-2">
							<span class="small text-muted">
								<b><?= htmlspecialchars($_SESSION['info']['member_name'] ?? $_SESSION['member_id'] ?? '') ?></b>님
							</span>
							<a href="/Auth/logout" class="btn btn-sm btn-outline-secondary">
								<i class="fas fa-sign-out-alt me-1"></i>로그아웃
							</a>
						</li>
						<?php endif; ?>
					</ul>
				</div>
			</nav>
			<!-- End Navbar -->
		</div>
