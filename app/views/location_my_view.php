<?php
// header.php에서 <head> 안에 카카오맵 SDK를 주입하도록 플래그 설정
$load_kakao_maps = true;
?>
<?php include APP_PATH . '/views/layouts/head.php'; ?>
<?php include APP_PATH . '/views/layouts/header.php'; ?>

<div class="wrapper">
	<?php include APP_PATH . '/views/layouts/side_menu.php'; ?>
	<div class="main-panel">
		<?php include APP_PATH . '/views/layouts/nav.php'; ?>
		<div class="container">
			<div class="page-inner pb-5">

				<!-- 헤더 -->
				<div class="d-flex align-items-center justify-content-between mb-3 mt-2">
					<h5 class="mb-0 fw-bold"><i class="fas fa-map-marked-alt text-primary me-2"></i>내 동선 확인</h5>
					<div class="d-flex gap-2 align-items-center">
						<!-- 날짜 선택 -->
						<form method="GET" action="/Location/myRoute" class="d-flex gap-1">
							<input type="date" name="date" class="form-control form-control-sm" style="width:145px;"
							       value="<?= htmlspecialchars($date ?? date('Y-m-d')) ?>">
							<button type="submit" class="btn btn-outline-primary btn-sm">조회</button>
						</form>
						<!-- 위치 추적 토글 -->
						<div class="form-check form-switch mb-0 ms-2 d-flex align-items-center gap-2">
							<input class="form-check-input" type="checkbox" id="tracking-toggle" role="switch" style="width:42px;height:22px;">
							<label class="form-check-label small fw-bold" for="tracking-toggle" id="tracking-label">
								<span id="tracking-indicator" style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#aaa;margin-right:4px;"></span>
								<span id="tracking-text">추적 꺼짐</span>
							</label>
						</div>
					</div>
				</div>

				<!-- 오늘 이동 요약 카드 -->
				<div class="row g-2 mb-3">
					<div class="col-4">
						<div class="card border-0 shadow-sm text-center py-2">
							<div class="small text-muted">총 이동거리</div>
							<div class="fw-bold text-primary" id="stat-distance">
								<?php
								$total_km = array_sum(array_column($sessions, 'total_distance'));
								echo number_format($total_km, 2) . ' km';
								?>
							</div>
						</div>
					</div>
					<div class="col-4">
						<div class="card border-0 shadow-sm text-center py-2">
							<div class="small text-muted">추적 포인트</div>
							<div class="fw-bold text-success" id="stat-points">
								<?= array_sum(array_column($sessions, 'point_count')) ?>개
							</div>
						</div>
					</div>
					<div class="col-4">
						<div class="card border-0 shadow-sm text-center py-2">
							<div class="small text-muted">세션 수</div>
							<div class="fw-bold text-info"><?= count($sessions) ?>회</div>
						</div>
					</div>
				</div>

				<!-- 지도 영역 -->
				<div class="card border-0 shadow-sm">
					<div class="card-header bg-white border-bottom py-2">
						<span class="fw-bold small"><i class="fas fa-route me-1 text-primary"></i> 이동 경로</span>
						<span class="small text-muted ms-2"><?= htmlspecialchars($date ?? date('Y-m-d')) ?></span>
					</div>
					<div class="card-body p-0">
						<?php if (KAKAO_MAP_KEY): ?>
						<div id="map" style="width:100%;height:500px;"></div>
						<?php else: ?>
						<div class="text-center py-5 text-muted">
							<i class="fas fa-map fa-3x mb-3 d-block text-muted"></i>
							<p class="mb-1">카카오맵 API 키가 설정되지 않았습니다.</p>
							<small><code>app/helpers/config.php</code>의 <code>KAKAO_MAP_KEY</code>를 설정해주세요.</small>
						</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- 세션 목록 -->
				<?php if (!empty($sessions)): ?>
				<div class="card border-0 shadow-sm mt-3">
					<div class="card-header bg-white border-bottom py-2">
						<span class="fw-bold small"><i class="fas fa-list me-1 text-primary"></i> 세션 목록</span>
					</div>
					<div class="card-body p-0">
						<table class="table table-sm table-hover mb-0">
							<thead class="table-light">
								<tr>
									<th class="ps-3">시작</th>
									<th>종료</th>
									<th class="text-center">포인트</th>
									<th class="text-end pe-3">이동거리</th>
									<th class="text-center">상태</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($sessions as $s): ?>
								<tr>
									<td class="ps-3 small"><?= substr($s['start_time'], 11, 5) ?></td>
									<td class="small"><?= $s['end_time'] ? substr($s['end_time'], 11, 5) : '-' ?></td>
									<td class="text-center small"><?= (int)$s['point_count'] ?></td>
									<td class="text-end pe-3 small"><?= number_format((float)$s['total_distance'], 2) ?> km</td>
									<td class="text-center">
										<?php if ($s['status'] === 'active'): ?>
										<span class="badge bg-success small">추적 중</span>
										<?php else: ?>
										<span class="badge bg-secondary small">완료</span>
										<?php endif; ?>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php endif; ?>

			</div>
		</div>
		<?php include APP_PATH . '/views/layouts/footer.php'; ?>
	</div>
</div>

<?php include APP_PATH . '/views/layouts/script.php'; ?>
<script src="/assets/js/location-tracker.js"></script>
<script>
$(function () {
	const todayDate  = '<?= htmlspecialchars($date ?? date('Y-m-d')) ?>';
	const myMemberId = '<?= htmlspecialchars($_SESSION['member_id'] ?? '') ?>';
	const hasMapKey  = <?= KAKAO_MAP_KEY ? 'true' : 'false' ?>;

	let map      = null;
	let polyline = null;

	// ── 카카오맵 초기화 (동기 로드: kakao.maps.Map 직접 사용) ──
	if (hasMapKey && typeof kakao !== 'undefined') {
		map = new kakao.maps.Map(document.getElementById('map'), {
			center: new kakao.maps.LatLng(37.5665, 126.9780),
			level: 7,
		});
		loadRoute(myMemberId, todayDate);
	}

	function loadRoute(memberId, date) {
		$.get('/Location/getRoute', { member_id: memberId, date: date }, function (res) {
			if (!res.success || res.points.length === 0) return;

			var path = res.points.map(function (p) {
				return new kakao.maps.LatLng(p.lat, p.lng);
			});

			if (polyline) polyline.setMap(null);

			polyline = new kakao.maps.Polyline({
				path: path,
				strokeWeight: 4,
				strokeColor: '#2E75B6',
				strokeOpacity: 0.85,
				strokeStyle: 'solid',
			});
			polyline.setMap(map);

			new kakao.maps.Marker({ position: path[0],              map: map, title: '시작' });
			new kakao.maps.Marker({ position: path[path.length - 1], map: map, title: '현재' });

			var bounds = new kakao.maps.LatLngBounds();
			path.forEach(function (p) { bounds.extend(p); });
			map.setBounds(bounds);
		}, 'json');
	}

	// ── 위치 추적 토글 UI ──
	function setTrackingUI(active) {
		$('#tracking-toggle').prop('checked', active);
		$('#tracking-indicator').css('background', active ? '#28a745' : '#aaa');
		$('#tracking-text').text(active ? '추적 중' : '추적 꺼짐');
	}

	// 페이지 로드 시 추적 상태 확인
	LocationTracker.checkStatus(function (tracking) {
		setTrackingUI(tracking);
	});

	$('#tracking-toggle').on('change', function () {
		if ($(this).is(':checked')) {
			LocationTracker.start(
				function () { setTrackingUI(true); },
				function (err) { alert(err); setTrackingUI(false); }
			);
		} else {
			LocationTracker.stop(function () {
				setTrackingUI(false);
				if (hasMapKey) loadRoute(myMemberId, todayDate);
			});
		}
	});
});
</script>
<?php include APP_PATH . '/views/layouts/tail.php'; ?>
