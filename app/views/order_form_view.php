<?php include APP_PATH . '/views/layouts/head.php';?>
<?php include APP_PATH . '/views/layouts/header.php';?>

	<div class="wrapper">

		<?php include APP_PATH . '/views/layouts/side_menu.php';?>

		<div class="main-panel">
			<?php include APP_PATH . '/views/layouts/nav.php';?>
			<div class="container">
				<div class="page-inner">

					<h3 class="fw-bold mb-3">고객정보 입력</h3>

					<div class="form-group">
						<label class="form-label fs-6 fw-bold">고객구분</label>
						<div class="selectgroup w-100">
							<label class="selectgroup-item">
								<input type="radio" name="customer_type" value="P" class="selectgroup-input" checked>
								<span class="selectgroup-button">개인</span>
							</label>
							<label class="selectgroup-item">
								<input type="radio" name="customer_type" value="B" class="selectgroup-input">
								<span class="selectgroup-button">개인사업자</span>
							</label>
						</div>
					</div>

					<div class="form-group">
						<label class="fs-6 fw-bold">고객명</label>
						<input type="text" class="form-control" id="customer_name" placeholder="이름 입력">
					</div>

					<div class="form-group">
						<label class="fs-6 fw-bold">휴대폰 번호</label>
						<input type="tel" class="form-control" id="customer_phone" placeholder="휴대폰 번호 11자리" maxlength="11">
					</div>

					<div class="form-group">
						<label class="form-label fs-6 fw-bold">상품 검색</label>
						<div class="d-flex align-items-center gap-2">
							<div class="input-icon flex-grow-1">
								<span class="input-icon-addon"><i class="fa fa-search"></i></span>
								<input
									type="text"
									class="form-control fs-6"
									placeholder="제품명/모델명"
									onkeypress="if(event.keyCode==13){search_product(this.value);}"
								/>
							</div>
							<button type="button" class="btn btn-outline-secondary" onclick="search_product($('.input-icon input').val());">
								검색
							</button>
						</div>
					</div>

					<div class="mt-3 mx-3">
						<label class="form-label fs-5 fw-bold">선택된 상품</label>
						<div id="model_list" class="d-flex flex-column gap-1">
							<div class="card text-center text-muted">
								<div class="card-body p-5 text-muted text-center">
									<span class="fs-5">
										제품을 선택해주세요.
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="mt-3 mx-3">
						<label class="form-label fs-5 fw-bold">합계</label>
						<div id="order_summary">
							<div class="card">
								<div class="card-body p-5 text-muted text-center">
									<span class="fs-5">
										제품을 선택해주세요.
									</span>
								</div>
							</div>
						</div>
					</div>

				</div>
			</div>

			<?php include APP_PATH . '/views/layouts/footer.php';?>
		</div>
	</div>

	<div class="modal fade" id="modal-default" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
		<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-md-down">
			<div class="modal-content">
				<div class="modal-header">
					<h6 class="modal-title fw-bold">상품 선택</h6>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body p-0">
					<div id="model-list-ul" class="list-group list-group-flush p-2"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
				</div>
			</div>
		</div>
	</div>

	<?php include APP_PATH . '/views/layouts/script.php';?>

<script>
	var model_modal = new bootstrap.Modal(document.getElementById('modal-default'));
	var selected_models = {};

	// 모델 검색
	function search_product(keyword)
	{
		if (document.activeElement) {
			document.activeElement.blur();
		}

		$.ajax({
			url: '/Product/getSearchProduct',
			method: 'POST',
			contentType: 'application/json',
			dataType: 'json',
			data: JSON.stringify({ keyword: keyword }),
			success: function(data)
			{
				if (data.status !== 'success' || !data.data || data.data.length === 0) {
					alert('상품을 찾을 수 없습니다');
					return;
				}

				render_model_modal(data.data);
				setTimeout(function() {
					model_modal.show();
				}, 100);
			}
		});
	}

	// 모델 모달 렌더링
	function render_model_modal(list)
	{
		var $wrap = $('#model-list-ul');
		$wrap.empty();

		$.each(list, function(idx, item)
		{
			var html = '';
			html += '<div class="card mb-2 position-relative">';
			html += '	<div class="card-body p-3">';
			html += '		<button type="button" class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-2 btn-select-model">선택</button>';
			html += '		<div class="d-flex align-items-start">';
			html += '			<img src="'+item.model_image+'" class="me-3 rounded" style="width:72px">';
			html += '			<div class="flex-grow-1">';
			html += '				<div class="fw-bold mb-1">'+item.model_name+'</div>';
			html += '				<div class="text-muted small mb-2">'+item.model_no+', '+item.model_color+'</div>';
			html += '				<div class="d-flex justify-content-between small mb-1">';
			html += '					<div class="text-muted">등록비/렌탈료</div>';
			html += '					<div class="fw-bold">'+number_format(item.setup_price)+' 원 / '+number_format(item.rent_price)+' 원</div>';
			html += '				</div>';
			html += '				<div class="d-flex justify-content-between small">';
			html += '					<div class="text-muted">일시불</div>';
			html += '					<div class="fw-bold">'+number_format(item.normal_price)+' 원</div>';
			html += '				</div>';
			html += '			</div>';
			html += '		</div>';
			html += '	</div>';
			html += '</div>';

			var $item = $(html);

			$item.find('.btn-select-model').on('click', function(e)
			{
				e.stopPropagation();
				append_selected_model(item);
				model_modal.hide();
			});

			$wrap.append($item);
		});
	}

	// 선택된 모델 추가
	function append_selected_model(item)
	{
		if (selected_models[item.uid]) {
			return;
		}

		selected_models[item.uid] = {
			uid: item.uid,
			model_name: item.model_name,
			model_no: item.model_no,
			model_image: item.model_image,
			model_detail_url: item.model_detail_url,
			model_color: item.model_color,
			setup_price: Number(item.setup_price),
			rent_price: Number(item.rent_price),
			discount_setup: 0,
			discount_rent: 0
		};

		render_selected_model_card(item.uid);
		render_order_summary();
	}

	// 선택된 모델 카드 렌더링
	function render_selected_model_card(uid)
	{
		var m = selected_models[uid];
		var final_setup = m.setup_price - m.discount_setup;
		var final_rent = m.rent_price - m.discount_rent;

		var html = '';
		html += '<div class="card mb-2" data-model-uid="'+uid+'">';
		html += '	<div class="card-body p-3">';
		html += '		<div class="d-flex align-items-start">';
		html += '			<img src="'+m.model_image+'" class="me-3 rounded" style="width:72px">';
		html += '			<div class="flex-grow-1">';
		html += '				<div class="fw-bold">'+m.model_name+'</div>';
		html += '				<div class="text-muted small mb-2">'+m.model_no+', '+m.model_color+'</div>';
		html += '			</div>';
		html += '			<div class="ms-2 d-flex gap-1">';
		html += '				<a class="btn btn-sm btn-secondary" href="'+m.model_detail_url+'" target="_blank"><i class="fas fa-share-square"></i></a>';
		html += '				<button class="btn btn-sm btn-info" onclick="open_discount_ui(\''+uid+'\')"><i class="fas fa-cog"></i></button>';
		html += '				<button class="btn btn-sm btn-danger" onclick="remove_model(\''+uid+'\')"><i class="fas fa-trash-alt"></i></button>';
		html += '			</div>';
		html += '		</div>';

html += '<div class="mt-3 border-top pt-3">';

html += '	<div class="row g-2">';
html += '		<div class="col-6">';
html += '			<label class="form-label small mb-1">방문주기</label>';
html += '			<select class="form-select form-select-sm">';
html += '				<option value="4">4개월</option>';
html += '				<option value="6">6개월</option>';
html += '			</select>';
html += '		</div>';

html += '		<div class="col-6">';
html += '			<label class="form-label small mb-1">의무사용기간</label>';
html += '			<select class="form-select form-select-sm">';
html += '				<option value="3">3년</option>';
html += '				<option value="5">5년</option>';
html += '				<option value="6">6년</option>';
html += '				<option value="7">7년</option>';
html += '				<option value="9">9년</option>';
html += '			</select>';
html += '		</div>';
html += '<div class="mt-3 border-top pt-3">';
html += '	<div class="fw-bold mb-2">프로모션 선택</div>';

html += '	<div class="form-check mb-1">';
html += '		<input class="form-check-input" type="checkbox" value="A141" id="promo_a141_'+uid+'">';
html += '		<label class="form-check-label small" for="promo_a141_'+uid+'">';
html += '			렌탈료 약정 할인 (월 6,000원)';
html += '		</label>';
html += '	</div>';

html += '	<div class="form-check mb-1">';
html += '		<input class="form-check-input" type="checkbox" value="A142" id="promo_a142_'+uid+'">';
html += '		<label class="form-check-label small" for="promo_a142_'+uid+'">';
html += '			렌탈료 10% 할인';
html += '		</label>';
html += '	</div>';

html += '	<div class="form-check mb-1">';
html += '		<input class="form-check-input" type="checkbox" value="A143" id="promo_a143_'+uid+'">';
html += '		<label class="form-check-label small" for="promo_a143_'+uid+'">';
html += '			설치비 전액 면제';
html += '		</label>';
html += '	</div>';

html += '	<div class="form-check mb-3">';
html += '		<input class="form-check-input" type="checkbox" value="A144" id="promo_a144_'+uid+'">';
html += '		<label class="form-check-label small" for="promo_a144_'+uid+'">';
html += '			렌탈료 3개월 무료';
html += '		</label>';
html += '	</div>';

html += '	<div class="row g-2">';
html += '		<div class="col-6">';
html += '			<label class="form-label small mb-1">방문주기</label>';
html += '			<select class="form-select form-select-sm">';
html += '				<option value="4">4개월</option>';
html += '				<option value="6">6개월</option>';
html += '			</select>';
html += '		</div>';

html += '		<div class="col-6">';
html += '			<label class="form-label small mb-1">의무사용기간</label>';
html += '			<select class="form-select form-select-sm">';
html += '				<option value="3">3년</option>';
html += '				<option value="5">5년</option>';
html += '				<option value="6">6년</option>';
html += '				<option value="7">7년</option>';
html += '				<option value="9">9년</option>';
html += '			</select>';
html += '		</div>';
html += '	</div>';
html += '</div>';

html += '	</div>';
html += '</div>';

		
		html += '		<div class="mt-3">';
		html += '			<div class="small d-flex justify-content-between mb-1"><div>상품금액</div><div>'+number_format(m.setup_price)+' 원 / '+number_format(m.rent_price)+' 원</div></div>';
		html += '			<div class="small d-flex justify-content-between mb-1 text-danger"><div>할인금액</div><div>-'+number_format(m.discount_setup)+' 원 / -'+number_format(m.discount_rent)+' 원</div></div>';
		html += '			<div class="fw-bold d-flex justify-content-between"><div>최종금액</div><div class="text-primary">'+number_format(final_setup)+' 원 / '+number_format(final_rent)+' 원</div></div>';
		html += '		</div>';
		html += '	</div>';
		html += '</div>';


		$('#model_list').append(html);
	}

	function open_discount_ui(uid)
	{
		selected_models[uid].discount_setup = selected_models[uid].setup_price;
		selected_models[uid].discount_rent = 8850;
		// render_selected_model_card(uid);
		render_order_summary();
	}

	function remove_model(uid)
	{
		delete selected_models[uid];
		$('#model_list').find('[data-model-uid="'+uid+'"]').remove();

		if (Object.keys(selected_models).length === 0) {
			$('#model_list').html('<div class="card text-center text-muted"><div class="card-body p-5 text-muted text-center"><span class="fs-5">제품을 선택해주세요.</span></div></div>');
		}

		render_order_summary();
	}

	function render_order_summary()
	{
		var total_setup = 0;
		var total_rent = 0;
		var count = 0;

		$.each(selected_models, function(_, m)
		{
			total_setup += (m.setup_price - m.discount_setup);
			total_rent += (m.rent_price - m.discount_rent);
			count++;
		});

		var html = '';
		html += '<div class="card">';
		html += '	<div class="card-body p-3">';
		html += '		<div class="fw-bold mb-2 text-primary">상품 합계</div>';
		html += '		<div class="small d-flex justify-content-between mb-1"><div>주문상품 개수</div><div>'+count+'개</div></div>';
		html += '		<div class="fw-bold d-flex justify-content-between border-top pt-2"><div>총 금액</div><div class="text-primary">'+number_format(total_setup)+'원 / '+number_format(total_rent)+'원</div></div>';
		html += '	</div>';
		html += '</div>';

		$('#order_summary').html(html);
	}

	function number_format(num)
	{
		return Number(num).toLocaleString('ko-KR');
	}
</script>

<?php include APP_PATH . '/views/layouts/tail.php';?>
