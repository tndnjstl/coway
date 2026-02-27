<?php include APP_PATH . '/views/layouts/head.php';?>
<?php include APP_PATH . '/views/layouts/header.php';?>

<div class="wrapper">

	<?php include APP_PATH . '/views/layouts/side_menu.php';?>

	<div class="main-panel">
		<?php include APP_PATH . '/views/layouts/nav.php';?>
		<div class="container">
			<div class="page-inner pb-5">

				<!-- ① 고객 정보 -->
				<div class="d-flex align-items-center mb-2 mt-2">
					<span class="badge bg-primary me-2 fs-6">1</span>
					<h5 class="mb-0 fw-bold">고객 정보</h5>
				</div>
				<div class="card mb-4">
					<div class="card-body">
						<div class="form-group mb-3">
							<label class="form-label fw-bold small">고객구분</label>
							<div class="selectgroup w-100">
								<label class="selectgroup-item">
									<input type="radio" name="customer_type" value="P" class="selectgroup-input" checked>
									<span class="selectgroup-button">개인</span>
								</label>
								<label class="selectgroup-item">
									<input type="radio" name="customer_type" value="B" class="selectgroup-input">
									<span class="selectgroup-button">개인사업자</span>
								</label>
								<label class="selectgroup-item">
									<input type="radio" name="customer_type" value="C" class="selectgroup-input">
									<span class="selectgroup-button">법인사업자</span>
								</label>
							</div>
						</div>
						<div class="row g-3">
							<div class="col-6">
								<label class="form-label fw-bold small">고객명</label>
								<input type="text" class="form-control" id="customer_name" placeholder="이름 입력">
							</div>
							<div class="col-6">
								<label class="form-label fw-bold small">휴대폰 번호</label>
								<input type="tel" class="form-control" id="customer_phone" placeholder="01012345678" maxlength="11">
							</div>
						</div>
					</div>
				</div>

				<!-- ② 상품 선택 -->
				<div class="d-flex align-items-center justify-content-between mb-2">
					<div class="d-flex align-items-center">
						<span class="badge bg-primary me-2 fs-6">2</span>
						<h5 class="mb-0 fw-bold">상품 선택</h5>
					</div>
					<button type="button" class="btn btn-primary btn-sm" onclick="open_product_modal()">
						<i class="fas fa-plus me-1"></i> 상품 추가
					</button>
				</div>

				<div id="model_list" class="mb-4">
					<div class="text-center text-muted py-5">
						<i class="fas fa-box-open fa-2x mb-2 d-block"></i>
						<span>상단 [상품 추가] 버튼을 눌러 상품을 추가해주세요.</span>
					</div>
				</div>

				<!-- ③ 메모 -->
				<div class="d-flex align-items-center mb-2">
					<span class="badge bg-primary me-2 fs-6">3</span>
					<h5 class="mb-0 fw-bold">메모</h5>
				</div>
				<div class="card mb-4">
					<div class="card-body">
						<textarea id="order_memo" class="form-control" rows="3" placeholder="특이사항, 요청사항 등 자유롭게 입력하세요."></textarea>
					</div>
				</div>

				<!-- ④ 합계 -->
				<div class="d-flex align-items-center mb-2">
					<span class="badge bg-primary me-2 fs-6">4</span>
					<h5 class="mb-0 fw-bold">합계</h5>
				</div>
				<div id="order_summary">
					<div class="card">
						<div class="card-body text-center text-muted py-4">
							상품을 선택하면 합계가 표시됩니다.
						</div>
					</div>
				</div>

				<!-- 저장 버튼 -->
				<div class="d-grid mt-4">
					<button type="button" class="btn btn-success btn-lg" id="btn_save_order" onclick="save_order()">
						<i class="fas fa-save me-2"></i>주문 등록
					</button>
				</div>

			</div>
		</div>
		<?php include APP_PATH . '/views/layouts/footer.php';?>
	</div>
</div>

<!-- 상품 선택 모달 -->
<div class="modal fade" id="modal-product-search" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-md-down">
		<div class="modal-content">
			<div class="modal-header py-2">
				<h6 class="modal-title fw-bold">상품 검색</h6>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body p-0">
				<!-- 검색바 -->
				<div class="px-3 pt-3 pb-2 border-bottom">
					<div class="input-icon">
						<span class="input-icon-addon"><i class="fa fa-search"></i></span>
						<input type="text" id="product-search-input" class="form-control" placeholder="제품명 또는 모델번호 검색">
					</div>
				</div>
				<!-- 카테고리 탭 -->
				<div class="px-3 py-2 border-bottom" id="category-tabs">
					<span class="text-muted small">불러오는 중...</span>
				</div>
				<!-- 상품 목록 -->
				<div id="product-list-ul" class="p-3">
					<div class="text-center text-muted py-4">
						<div class="spinner-border spinner-border-sm me-2" role="status"></div>
						상품 목록을 불러오는 중...
					</div>
				</div>
			</div>
			<div class="modal-footer py-2">
				<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">닫기</button>
			</div>
		</div>
	</div>
</div>

<?php include APP_PATH . '/views/layouts/script.php';?>
<?php include APP_PATH . '/views/order_form_js.php';?>
<?php include APP_PATH . '/views/layouts/tail.php';?>
