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
		<div class="container-fluid">
			<div class="page-inner pb-5">

				<!-- 헤더 -->
				<div class="d-flex align-items-center justify-content-between mb-3 mt-2">
					<h5 class="mb-0 fw-bold"><i class="fas fa-map-marked-alt text-primary me-2"></i>동선 관리 대시보드</h5>
					<form method="GET" action="/Location/dashboard" class="d-flex gap-1">
						<input type="date" name="date" class="form-control form-control-sm" style="width:145px;"
						       value="<?= htmlspecialchars($date ?? date('Y-m-d')) ?>">
						<button type="submit" class="btn btn-primary btn-sm">조회</button>
					</form>
				</div>

				<div class="row g-3">
					<!-- 좌측: 영업자 목록 -->
					<div class="col-12 col-lg-4">
						<div class="card border-0 shadow-sm h-100">
							<div class="card-header bg-white border-bottom py-2">
								<span class="fw-bold small"><i class="fas fa-users me-1 text-primary"></i> 영업자 목록</span>
								<span class="small text-muted ms-1"><?= htmlspecialchars($date ?? date('Y-m-d')) ?></span>
							</div>
							<div class="card-body p-0" style="max-height:600px;overflow-y:auto;">
								<?php foreach ($members as $m): ?>
								<?php
								$s = $summary[$m['member_id']] ?? ['total_km' => 0, 'total_points' => 0, 'is_active' => false];
								?>
								<div class="member-card p-3 border-bottom hover-bg"
								     data-member="<?= htmlspecialchars($m['member_id']) ?>"
								     data-name="<?= htmlspecialchars($m['member_name']) ?>"
								     style="cursor:pointer;transition:background .15s;">
									<div class="d-flex align-items-center gap-2">
										<div class="flex-shrink-0">
											<div style="width:10px;height:10px;border-radius:50%;background:<?= $s['is_active'] ? '#28a745' : '#ccc' ?>;<?= $s['is_active'] ? 'box-shadow:0 0 0 3px rgba(40,167,69,.25);' : '' ?>"></div>
										</div>
										<div class="flex-grow-1">
											<div class="fw-bold small"><?= htmlspecialchars($m['member_name']) ?></div>
											<div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($m['member_id']) ?></div>
										</div>
										<div class="text-end">
											<div class="small fw-bold text-primary"><?= $s['total_km'] ?> km</div>
											<div style="font-size:11px;color:#aaa;"><?= $s['total_points'] ?>포인트</div>
										</div>
										<?php if ($s['is_active']): ?>
										<span class="badge bg-success" style="font-size:10px;">추적 중</span>
										<?php endif; ?>
									</div>
								</div>
								<?php endforeach; ?>
								<?php if (empty($members)): ?>
								<div class="text-center py-4 text-muted small">영업자가 없습니다.</div>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<!-- 우측: 지도 -->
					<div class="col-12 col-lg-8">
						<div class="card border-0 shadow-sm">
							<div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between">
								<span class="fw-bold small"><i class="fas fa-route me-1 text-primary"></i> 이동 경로</span>
								<span class="small text-muted" id="map-subtitle">영업자를 선택하면 경로가 표시됩니다</span>
							</div>
							<div class="card-body p-0">
								<?php if (KAKAO_MAP_KEY): ?>
								<div id="map" style="width:100%;height:550px;"></div>
								<?php else: ?>
								<div class="text-center py-5 text-muted">
									<i class="fas fa-map fa-3x mb-3 d-block"></i>
									<p class="mb-1">카카오맵 API 키가 설정되지 않았습니다.</p>
									<small><code>KAKAO_MAP_KEY</code>를 설정해주세요.</small>
								</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
		<?php include APP_PATH . '/views/layouts/footer.php'; ?>
	</div>
</div>

<?php include APP_PATH . '/views/layouts/script.php'; ?>
<script src="/assets/js/location-tracker.js"></script>
<script>
$(function () {
	const selectedDate = '<?= htmlspecialchars($date ?? date('Y-m-d')) ?>';
	const hasMapKey    = <?= KAKAO_MAP_KEY ? 'true' : 'false' ?>;

	// 영업자별 색상 (최대 5명)
	const COLORS = ['#2E75B6', '#E05C2D', '#1E8C45', '#8B1EC0', '#C09B00'];
	let colorIndex = 0;
	let polylines  = {};  // member_id → kakao.maps.Polyline
	let map        = null;

	if (hasMapKey && typeof kakao !== 'undefined') {
		map = new kakao.maps.Map(document.getElementById('map'), {
			center: new kakao.maps.LatLng(37.5665, 126.9780),
			level: 8,
		});
	}

	// 영업자 카드 클릭 - 경로 토글
	$(document).on('click', '.member-card', function () {
		const memberId = $(this).data('member');
		const name     = $(this).data('name');

		// 이미 표시 중이면 제거
		if (polylines[memberId]) {
			polylines[memberId].lines.forEach(function (l) { l.setMap(null); });
			delete polylines[memberId];
			$(this).css('background', '');
			$('#map-subtitle').text('영업자를 선택하면 경로가 표시됩니다');
			return;
		}

		// 최대 5개 초과 시 안내
		if (Object.keys(polylines).length >= 5) {
			alert('최대 5명까지 동시 표시 가능합니다.');
			return;
		}

		const color = COLORS[colorIndex % COLORS.length];
		colorIndex++;
		$(this).css('background', color + '18');

		$.get('/Location/getRoute', { member_id: memberId, date: selectedDate }, function (res) {
			if (!res.success || res.points.length === 0) {
				alert(name + '님의 이동 데이터가 없습니다.');
				$(this).css('background', '');
				return;
			}

			if (!hasMapKey) return;

			const path = res.points.map(function (p) {
				return new kakao.maps.LatLng(p.lat, p.lng);
			});

			const line = new kakao.maps.Polyline({
				path: path,
				strokeWeight: 4,
				strokeColor: color,
				strokeOpacity: 0.85,
				strokeStyle: 'solid',
			});
			line.setMap(map);

			// 시작/종료 마커
			const startMarker = new kakao.maps.Marker({ position: path[0], map: map });
			const endMarker   = new kakao.maps.Marker({ position: path[path.length - 1], map: map });

			// 마커에 이름 인포윈도우
			const infoStart = new kakao.maps.InfoWindow({ content: '<div style="padding:4px 8px;font-size:12px;">' + name + ' 시작</div>' });
			kakao.maps.event.addListener(startMarker, 'click', function () { infoStart.open(map, startMarker); });

			polylines[memberId] = { lines: [line], markers: [startMarker, endMarker] };

			// 지도 범위 조정
			const bounds = new kakao.maps.LatLngBounds();
			path.forEach(function (p) { bounds.extend(p); });
			map.setBounds(bounds);

			$('#map-subtitle').text(name + ' 경로 표시 중 (' + res.count + '포인트)');
		}.bind(this), 'json');
	});

	// 호버 스타일
	$(document).on('mouseenter', '.member-card', function () {
		if (!polylines[$(this).data('member')]) $(this).css('background', '#f0f4ff');
	}).on('mouseleave', '.member-card', function () {
		if (!polylines[$(this).data('member')]) $(this).css('background', '');
	});
});
</script>
<?php include APP_PATH . '/views/layouts/tail.php'; ?>
