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
			visit_cycle: '4',
			duty_year: '3',
			promotions: {
				A141: false,
				A142: false,
				A143: false,
				A144: false
			}
		};

		if (Object.keys(selected_models).length === 1) {
			$('#model_list').empty();
		}

		render_selected_model_card(item.uid);
		render_order_summary();
	}

	function get_model_pricing(m)
	{
		var base_setup = Number(m.setup_price) || 0;
		var base_rent = Number(m.rent_price) || 0;

		var rent_discount = 0;
		if (m.promotions.A141) {
			rent_discount += 6000;
		}
		if (m.promotions.A142) {
			rent_discount += Math.floor(base_rent * 0.10);
		}

		var setup_discount = 0;
		if (m.promotions.A143) {
			setup_discount = base_setup;
		}

		var final_setup = Math.max(0, base_setup - setup_discount);
		var final_rent = Math.max(0, base_rent - rent_discount);

		var contract_month = Number(m.duty_year) * 12;
		var free_month = m.promotions.A144 ? Math.min(3, contract_month) : 0;
		var contract_rent_total = Math.max(0, final_rent * (contract_month - free_month));
		var total_pay = final_setup + contract_rent_total;

		return {
			base_setup: base_setup,
			base_rent: base_rent,
			rent_discount: rent_discount,
			setup_discount: setup_discount,
			final_setup: final_setup,
			final_rent: final_rent,
			contract_month: contract_month,
			free_month: free_month,
			contract_rent_total: contract_rent_total,
			total_pay: total_pay
		};
	}

	function escape_html(str)
	{
		return String(str || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	}

	// 선택된 모델 카드 렌더링
	function render_selected_model_card(uid)
	{
		var m = selected_models[uid];
		if (!m) {
			return;
		}

		var p = get_model_pricing(m);

		var html = '';
		html += '<div class="card mb-2" data-model-uid="'+uid+'">';
		html += '	<div class="card-body p-3">';
		html += '		<div class="d-flex align-items-start">';
		html += '			<img src="'+escape_html(m.model_image)+'" class="me-3 rounded" style="width:72px">';
		html += '			<div class="flex-grow-1">';
		html += '				<div class="fw-bold">'+escape_html(m.model_name)+'</div>';
		html += '				<div class="text-muted small mb-2">'+escape_html(m.model_no)+', '+escape_html(m.model_color)+'</div>';
		html += '			</div>';
		html += '			<div class="ms-2 d-flex gap-1">';
		html += '				<a class="btn btn-sm btn-secondary" href="'+escape_html(m.model_detail_url)+'" target="_blank"><i class="fas fa-share-square"></i></a>';
		html += '				<button class="btn btn-sm btn-danger" onclick="remove_model(\''+uid+'\')"><i class="fas fa-trash-alt"></i></button>';
		html += '			</div>';
		html += '		</div>';
		html += '		<div class="mt-3 border-top pt-3">';
		html += '			<div class="row g-2">';
		html += '				<div class="col-6">';
		html += '					<label class="form-label small mb-1">방문주기</label>';
		html += '					<select class="form-select form-select-sm visit-cycle-select" data-model-uid="'+uid+'">';
		html += '						<option value="4" '+(m.visit_cycle === '4' ? 'selected' : '')+'>4개월</option>';
		html += '						<option value="6" '+(m.visit_cycle === '6' ? 'selected' : '')+'>6개월</option>';
		html += '					</select>';
		html += '				</div>';
		html += '				<div class="col-6">';
		html += '					<label class="form-label small mb-1">의무사용기간</label>';
		html += '					<select class="form-select form-select-sm duty-year-select" data-model-uid="'+uid+'">';
		html += '						<option value="3" '+(m.duty_year === '3' ? 'selected' : '')+'>3년</option>';
		html += '						<option value="5" '+(m.duty_year === '5' ? 'selected' : '')+'>5년</option>';
		html += '						<option value="6" '+(m.duty_year === '6' ? 'selected' : '')+'>6년</option>';
		html += '						<option value="7" '+(m.duty_year === '7' ? 'selected' : '')+'>7년</option>';
		html += '						<option value="9" '+(m.duty_year === '9' ? 'selected' : '')+'>9년</option>';
		html += '					</select>';
		html += '				</div>';
		html += '			</div>';
		html += '			<div class="mt-3 border-top pt-3">';
		html += '				<div class="fw-bold mb-2">프로모션 선택</div>';
		html += '				<div class="form-check mb-1">';
		html += '					<input class="form-check-input promo-check" type="checkbox" value="A141" data-model-uid="'+uid+'" id="promo_a141_'+uid+'" '+(m.promotions.A141 ? 'checked' : '')+'>';
		html += '					<label class="form-check-label small" for="promo_a141_'+uid+'">렌탈료 약정 할인 (월 6,000원)</label>';
		html += '				</div>';
		html += '				<div class="form-check mb-1">';
		html += '					<input class="form-check-input promo-check" type="checkbox" value="A142" data-model-uid="'+uid+'" id="promo_a142_'+uid+'" '+(m.promotions.A142 ? 'checked' : '')+'>';
		html += '					<label class="form-check-label small" for="promo_a142_'+uid+'">렌탈료 10% 할인</label>';
		html += '				</div>';
		html += '				<div class="form-check mb-1">';
		html += '					<input class="form-check-input promo-check" type="checkbox" value="A143" data-model-uid="'+uid+'" id="promo_a143_'+uid+'" '+(m.promotions.A143 ? 'checked' : '')+'>';
		html += '					<label class="form-check-label small" for="promo_a143_'+uid+'">설치비 전액 면제</label>';
		html += '				</div>';
		html += '				<div class="form-check mb-0">';
		html += '					<input class="form-check-input promo-check" type="checkbox" value="A144" data-model-uid="'+uid+'" id="promo_a144_'+uid+'" '+(m.promotions.A144 ? 'checked' : '')+'>';
		html += '					<label class="form-check-label small" for="promo_a144_'+uid+'">렌탈료 3개월 무료</label>';
		html += '				</div>';
		html += '			</div>';
		html += '		</div>';
		html += '		<div class="mt-3 border-top pt-3">';
		html += '			<div class="small d-flex justify-content-between mb-1"><div>기본금액(등록비/월렌탈)</div><div>'+number_format(p.base_setup)+' 원 / '+number_format(p.base_rent)+' 원</div></div>';
		html += '			<div class="small d-flex justify-content-between mb-1 text-danger"><div>할인금액(등록비/월렌탈)</div><div>-'+number_format(p.setup_discount)+' 원 / -'+number_format(p.rent_discount)+' 원</div></div>';
		html += '			<div class="small d-flex justify-content-between mb-1"><div>최종금액(등록비/월렌탈)</div><div class="fw-bold text-primary">'+number_format(p.final_setup)+' 원 / '+number_format(p.final_rent)+' 원</div></div>';
		html += '			<div class="small d-flex justify-content-between mb-1"><div>약정개월</div><div>'+p.contract_month+'개월</div></div>';
		html += '			<div class="small d-flex justify-content-between mb-1"><div>약정 총 렌탈료</div><div>'+number_format(p.contract_rent_total)+' 원</div></div>';
		html += '			<div class="fw-bold d-flex justify-content-between"><div>총 납부 예상액</div><div class="text-primary">'+number_format(p.total_pay)+' 원</div></div>';
		html += '		</div>';
		html += '	</div>';
		html += '</div>';

		var $existing = $('#model_list').find('[data-model-uid="'+uid+'"]');
		if ($existing.length > 0) {
			$existing.replaceWith(html);
		} else {
			$('#model_list').append(html);
		}
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
		var total_monthly_rent = 0;
		var total_contract_rent = 0;
		var total_pay = 0;
		var count = 0;

		$.each(selected_models, function(_, m)
		{
			var p = get_model_pricing(m);
			total_setup += p.final_setup;
			total_monthly_rent += p.final_rent;
			total_contract_rent += p.contract_rent_total;
			total_pay += p.total_pay;
			count++;
		});

		if (count === 0) {
			$('#order_summary').html('<div class="card"><div class="card-body p-5 text-muted text-center"><span class="fs-5">제품을 선택해주세요.</span></div></div>');
			return;
		}

		var html = '';
		html += '<div class="card">';
		html += '	<div class="card-body p-3">';
		html += '		<div class="fw-bold mb-2 text-primary">상품 합계</div>';
		html += '		<div class="small d-flex justify-content-between mb-1"><div>주문상품 개수</div><div>'+count+'개</div></div>';
		html += '		<div class="small d-flex justify-content-between mb-1"><div>등록비 합계</div><div>'+number_format(total_setup)+' 원</div></div>';
		html += '		<div class="small d-flex justify-content-between mb-1"><div>월 렌탈료 합계</div><div>'+number_format(total_monthly_rent)+' 원</div></div>';
		html += '		<div class="small d-flex justify-content-between mb-1"><div>약정 총 렌탈료</div><div>'+number_format(total_contract_rent)+' 원</div></div>';
		html += '		<div class="fw-bold d-flex justify-content-between border-top pt-2"><div>총 납부 예상액</div><div class="text-primary">'+number_format(total_pay)+' 원</div></div>';
		html += '	</div>';
		html += '</div>';

		$('#order_summary').html(html);
	}

	$(document).on('change', '.visit-cycle-select', function()
	{
		var uid = $(this).data('model-uid');
		if (!selected_models[uid]) {
			return;
		}
		selected_models[uid].visit_cycle = String($(this).val());
		render_selected_model_card(uid);
		render_order_summary();
	});

	$(document).on('change', '.duty-year-select', function()
	{
		var uid = $(this).data('model-uid');
		if (!selected_models[uid]) {
			return;
		}
		selected_models[uid].duty_year = String($(this).val());
		render_selected_model_card(uid);
		render_order_summary();
	});

	$(document).on('change', '.promo-check', function()
	{
		var uid = $(this).data('model-uid');
		var promoCode = $(this).val();
		if (!selected_models[uid] || !selected_models[uid].promotions.hasOwnProperty(promoCode)) {
			return;
		}
		selected_models[uid].promotions[promoCode] = $(this).is(':checked');
		render_selected_model_card(uid);
		render_order_summary();
	});

	function number_format(num)
	{
		return Number(num).toLocaleString('ko-KR');
	}
</script>

<?php include APP_PATH . '/views/layouts/tail.php';?>
