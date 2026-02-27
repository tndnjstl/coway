<?php ?>
<script>
/* =============================================================
 * 상태
 * ============================================================= */
var all_products    = [];  // 전체 상품 캐시
var selected_models = {};  // 선택된 상품 { uid: {...} }
var active_category = '';  // 현재 카테고리 필터

var product_modal = new bootstrap.Modal(document.getElementById('modal-product-search'));

/* =============================================================
 * 모달 열기 / 상품 로드
 * ============================================================= */
function open_product_modal() {
	product_modal.show();

	if (all_products.length > 0) {
		render_category_tabs(all_products);
		render_modal_list(filter_products());
		return;
	}

	$.ajax({
		url: '/Product/getSearchProduct',
		method: 'POST',
		contentType: 'application/json',
		dataType: 'json',
		data: JSON.stringify({ keyword: '' }),
		success: function(res) {
			if (res.status !== 'success' || !res.data) return;
			all_products = res.data;
			render_category_tabs(all_products);
			render_modal_list(filter_products());
		},
		error: function() {
			$('#product-list-ul').html('<div class="text-center text-danger py-4">상품 로드 실패. 다시 시도해주세요.</div>');
		}
	});
}

/* =============================================================
 * 카테고리 탭 렌더링
 * ============================================================= */
function render_category_tabs(products) {
	var cats = [];
	$.each(products, function(_, p) {
		if (p.category && cats.indexOf(p.category) === -1) {
			cats.push(p.category);
		}
	});
	cats.sort();

	var html = '<button class="btn btn-sm ' + (active_category === '' ? 'btn-primary' : 'btn-outline-secondary') + ' category-tab me-1 mb-1" data-cat="">전체</button>';
	$.each(cats, function(_, cat) {
		var cls = (active_category === cat) ? 'btn-primary' : 'btn-outline-secondary';
		html += '<button class="btn btn-sm ' + cls + ' category-tab me-1 mb-1" data-cat="' + escape_html(cat) + '">' + escape_html(cat) + '</button>';
	});

	$('#category-tabs').html(html);
}

/* =============================================================
 * 필터 (카테고리 + 키워드)
 * ============================================================= */
function filter_products() {
	var keyword = $('#product-search-input').val().toLowerCase().trim();

	return all_products.filter(function(p) {
		var cat_ok = (active_category === '' || p.category === active_category);
		var kw_ok  = (keyword === '') ||
			(p.model_name && p.model_name.toLowerCase().indexOf(keyword) !== -1) ||
			(p.model_no   && p.model_no.toLowerCase().indexOf(keyword)   !== -1);
		return cat_ok && kw_ok;
	});
}

/* =============================================================
 * 모달 상품 목록 렌더링
 * ============================================================= */
function render_modal_list(list) {
	var $wrap = $('#product-list-ul');
	$wrap.empty();

	if (list.length === 0) {
		$wrap.html('<div class="text-center text-muted py-4">검색 결과가 없습니다.</div>');
		return;
	}

	$.each(list, function(_, item) {
		var is_selected = !!selected_models[item.uid];
		var btn_cls     = is_selected ? 'btn-success disabled' : 'btn-outline-primary btn-select-model';
		var btn_txt     = is_selected ? '<i class="fas fa-check me-1"></i>선택됨' : '선택';

		var img_url = item.model_image || '';
		if (img_url && img_url.indexOf('http') !== 0) {
			img_url = 'https://www.coway.com' + img_url;
		}

		var html = '';
		html += '<div class="card mb-2">';
		html += '  <div class="card-body p-2">';
		html += '    <div class="d-flex align-items-center gap-2">';
		html += '      <img src="' + escape_html(img_url) + '" class="rounded flex-shrink-0" style="width:56px;height:56px;object-fit:contain;" onerror="this.src=\'\';">';
		html += '      <div class="flex-grow-1 min-w-0">';
		html += '        <div class="fw-bold text-truncate">' + escape_html(item.model_name) + '</div>';
		html += '        <div class="text-muted small">' + escape_html(item.model_no) + ' · ' + escape_html(item.model_color) + '</div>';
		html += '        <div class="small mt-1">';
		html += '          <span class="badge bg-light text-secondary border me-1">' + escape_html(item.category) + '</span>';
		html += '          <span class="text-primary fw-bold">렌탈 ' + number_format(item.rent_price) + '원</span>';
		html += '          <span class="text-muted ms-1">| 일시불 ' + number_format(item.normal_price) + '원</span>';
		html += '        </div>';
		html += '      </div>';
		html += '      <div class="flex-shrink-0">';
		html += '        <button type="button" class="btn btn-sm ' + btn_cls + '" data-uid="' + escape_html(String(item.uid)) + '">' + btn_txt + '</button>';
		html += '      </div>';
		html += '    </div>';
		html += '  </div>';
		html += '</div>';

		var $el = $(html);

		if (!is_selected) {
			$el.find('.btn-select-model').on('click', function() {
				append_selected_model(item);
				$(this)
					.removeClass('btn-outline-primary btn-select-model')
					.addClass('btn-success disabled')
					.html('<i class="fas fa-check me-1"></i>선택됨');
			});
		}

		$wrap.append($el);
	});
}

/* =============================================================
 * 선택된 상품 추가
 * ============================================================= */
function append_selected_model(item) {
	if (selected_models[item.uid]) return;

	var img_url = item.model_image || '';
	if (img_url && img_url.indexOf('http') !== 0) {
		img_url = 'https://www.coway.com' + img_url;
	}

	selected_models[item.uid] = {
		uid:              item.uid,
		model_name:       item.model_name,
		model_no:         item.model_no,
		model_image:      img_url,
		model_detail_url: item.model_detail_url,
		model_color:      item.model_color,
		category:         item.category,
		setup_price:      Number(item.setup_price)  || 0,
		rent_price:       Number(item.rent_price)   || 0,
		normal_price:     Number(item.normal_price) || 0,
		payment_type:     'rent',  // 'rent' | 'buy'
		visit_cycle:      '4',
		duty_year:        '3',
		promotions: { A141: false, A142: false, A143: false, A144: false }
	};

	if ($('#model_list').find('.text-center.text-muted').length > 0) {
		$('#model_list').empty();
	}

	render_selected_model_card(item.uid);
	render_order_summary();
}

/* =============================================================
 * 가격 계산
 * ============================================================= */
function get_model_pricing(m) {
	if (m.payment_type === 'buy') {
		return { payment_type: 'buy', normal_price: m.normal_price, total_pay: m.normal_price };
	}

	var base_setup = m.setup_price;
	var base_rent  = m.rent_price;

	var rent_discount  = 0;
	if (m.promotions.A141) rent_discount += 6000;
	if (m.promotions.A142) rent_discount += Math.floor(base_rent * 0.10);

	var setup_discount = m.promotions.A143 ? base_setup : 0;
	var final_setup    = Math.max(0, base_setup - setup_discount);
	var final_rent     = Math.max(0, base_rent  - rent_discount);

	var contract_month      = Number(m.duty_year) * 12;
	var free_month          = m.promotions.A144 ? Math.min(3, contract_month) : 0;
	var contract_rent_total = Math.max(0, final_rent * (contract_month - free_month));
	var total_pay           = final_setup + contract_rent_total;

	return {
		payment_type: 'rent',
		base_setup, base_rent, rent_discount, setup_discount,
		final_setup, final_rent, contract_month, free_month,
		contract_rent_total, total_pay
	};
}

/* =============================================================
 * 선택된 상품 카드 렌더링
 * ============================================================= */
function render_selected_model_card(uid) {
	var m = selected_models[uid];
	if (!m) return;

	var p       = get_model_pricing(m);
	var is_rent = (m.payment_type === 'rent');

	var html = '';
	html += '<div class="card mb-3" data-model-uid="' + uid + '">';
	html += '  <div class="card-body p-3">';

	/* 상단 헤더 */
	html += '  <div class="d-flex align-items-start gap-2">';
	html += '    <img src="' + escape_html(m.model_image) + '" class="rounded flex-shrink-0" style="width:68px;height:68px;object-fit:contain;" onerror="this.src=\'\';">';
	html += '    <div class="flex-grow-1 min-w-0">';
	html += '      <div class="fw-bold">' + escape_html(m.model_name) + '</div>';
	html += '      <div class="text-muted small">' + escape_html(m.model_no) + ' · ' + escape_html(m.model_color) + '</div>';
	html += '      <span class="badge bg-light text-secondary border mt-1">' + escape_html(m.category) + '</span>';
	html += '    </div>';
	html += '    <div class="d-flex gap-1 flex-shrink-0">';
	html += '      <a class="btn btn-sm btn-outline-secondary" href="' + escape_html(m.model_detail_url) + '" target="_blank" title="상세페이지"><i class="fas fa-external-link-alt"></i></a>';
	html += '      <button class="btn btn-sm btn-outline-danger" onclick="remove_model(\'' + uid + '\')" title="삭제"><i class="fas fa-times"></i></button>';
	html += '    </div>';
	html += '  </div>';

	/* 렌탈 / 일시불 토글 */
	html += '  <div class="mt-3">';
	html += '    <div class="btn-group w-100" role="group">';
	html += '      <button type="button" class="btn btn-sm ' + (is_rent ? 'btn-primary' : 'btn-outline-primary') + ' payment-toggle" data-model-uid="' + uid + '" data-type="rent">렌탈</button>';
	html += '      <button type="button" class="btn btn-sm ' + (!is_rent ? 'btn-primary' : 'btn-outline-primary') + ' payment-toggle" data-model-uid="' + uid + '" data-type="buy">일시불</button>';
	html += '    </div>';
	html += '  </div>';

	if (is_rent) {
		html += '  <div class="mt-3 border-top pt-3">';

		/* 방문주기 / 의무사용기간 */
		html += '    <div class="row g-2 mb-3">';
		html += '      <div class="col-6">';
		html += '        <label class="form-label small fw-bold mb-1">방문주기</label>';
		html += '        <select class="form-select form-select-sm visit-cycle-select" data-model-uid="' + uid + '">';
		html += '          <option value="4" ' + (m.visit_cycle === '4' ? 'selected' : '') + '>4개월</option>';
		html += '          <option value="6" ' + (m.visit_cycle === '6' ? 'selected' : '') + '>6개월</option>';
		html += '        </select>';
		html += '      </div>';
		html += '      <div class="col-6">';
		html += '        <label class="form-label small fw-bold mb-1">의무사용기간</label>';
		html += '        <select class="form-select form-select-sm duty-year-select" data-model-uid="' + uid + '">';
		html += '          <option value="3" ' + (m.duty_year === '3' ? 'selected' : '') + '>3년</option>';
		html += '          <option value="5" ' + (m.duty_year === '5' ? 'selected' : '') + '>5년</option>';
		html += '          <option value="6" ' + (m.duty_year === '6' ? 'selected' : '') + '>6년</option>';
		html += '          <option value="7" ' + (m.duty_year === '7' ? 'selected' : '') + '>7년</option>';
		html += '          <option value="9" ' + (m.duty_year === '9' ? 'selected' : '') + '>9년</option>';
		html += '        </select>';
		html += '      </div>';
		html += '    </div>';

		/* 프로모션 */
		html += '    <div class="border rounded p-2 mb-3" style="background:#f8f9fa;">';
		html += '      <div class="small fw-bold mb-2 text-secondary">프로모션</div>';
		var promos = [
			{ code: 'A141', label: '렌탈료 약정 할인 (월 -6,000원)' },
			{ code: 'A142', label: '렌탈료 10% 할인' },
			{ code: 'A143', label: '설치비 전액 면제' },
			{ code: 'A144', label: '렌탈료 3개월 무료' }
		];
		$.each(promos, function(_, pr) {
			var chk = m.promotions[pr.code] ? 'checked' : '';
			var id  = 'promo_' + pr.code.toLowerCase() + '_' + uid;
			html += '<div class="form-check mb-1">';
			html += '  <input class="form-check-input promo-check" type="checkbox" value="' + pr.code + '" data-model-uid="' + uid + '" id="' + id + '" ' + chk + '>';
			html += '  <label class="form-check-label small" for="' + id + '">' + pr.label + '</label>';
			html += '</div>';
		});
		html += '    </div>';

		/* 렌탈 가격 요약 */
		html += '    <div class="rounded p-2" style="background:#eef2ff;">';
		html += '      <div class="small d-flex justify-content-between mb-1"><span class="text-muted">기본 등록비 / 월렌탈료</span><span>' + number_format(p.base_setup) + '원 / ' + number_format(p.base_rent) + '원</span></div>';
		if (p.setup_discount > 0 || p.rent_discount > 0) {
			html += '      <div class="small d-flex justify-content-between mb-1"><span class="text-danger">할인 (등록비 / 월렌탈료)</span><span class="text-danger">-' + number_format(p.setup_discount) + '원 / -' + number_format(p.rent_discount) + '원</span></div>';
		}
		html += '      <div class="small d-flex justify-content-between mb-1 fw-bold"><span>최종 등록비 / 월렌탈료</span><span class="text-primary">' + number_format(p.final_setup) + '원 / ' + number_format(p.final_rent) + '원</span></div>';
		var period_str = p.contract_month + '개월';
		if (p.free_month > 0) period_str += ' (무료 ' + p.free_month + '개월 포함)';
		html += '      <div class="small d-flex justify-content-between mb-1"><span class="text-muted">약정기간</span><span>' + period_str + '</span></div>';
		html += '      <div class="small fw-bold d-flex justify-content-between border-top pt-1 mt-1"><span>총 납부 예상액</span><span class="text-primary">' + number_format(p.total_pay) + '원</span></div>';
		html += '    </div>';

		html += '  </div>';

	} else {
		/* 일시불 */
		html += '  <div class="mt-3 border-top pt-3">';
		html += '    <div class="rounded p-3 d-flex justify-content-between align-items-center" style="background:#eef2ff;">';
		html += '      <span class="fw-bold">일시불 금액</span>';
		html += '      <span class="fs-5 fw-bold text-primary">' + number_format(m.normal_price) + '원</span>';
		html += '    </div>';
		html += '  </div>';
	}

	html += '  </div>';
	html += '</div>';

	var $existing = $('#model_list').find('[data-model-uid="' + uid + '"]');
	if ($existing.length > 0) {
		$existing.replaceWith(html);
	} else {
		$('#model_list').append(html);
	}
}

/* =============================================================
 * 상품 삭제
 * ============================================================= */
function remove_model(uid) {
	delete selected_models[uid];
	$('#model_list').find('[data-model-uid="' + uid + '"]').remove();

	if (Object.keys(selected_models).length === 0) {
		$('#model_list').html(
			'<div class="text-center text-muted py-5">' +
			'<i class="fas fa-box-open fa-2x mb-2 d-block"></i>' +
			'상단 [상품 추가] 버튼을 눌러 상품을 추가해주세요.' +
			'</div>'
		);
	}
	render_order_summary();
}

/* =============================================================
 * 합계 렌더링
 * ============================================================= */
function render_order_summary() {
	var total_setup         = 0;
	var total_monthly_rent  = 0;
	var total_contract_rent = 0;
	var total_buy           = 0;
	var rent_count          = 0;
	var buy_count           = 0;

	$.each(selected_models, function(_, m) {
		var p = get_model_pricing(m);
		if (p.payment_type === 'rent') {
			total_setup         += p.final_setup;
			total_monthly_rent  += p.final_rent;
			total_contract_rent += p.contract_rent_total;
			rent_count++;
		} else {
			total_buy += p.normal_price;
			buy_count++;
		}
	});

	var count     = rent_count + buy_count;
	var total_pay = total_setup + total_contract_rent + total_buy;

	if (count === 0) {
		$('#order_summary').html('<div class="card"><div class="card-body text-center text-muted py-4">상품을 선택하면 합계가 표시됩니다.</div></div>');
		return;
	}

	var html = '';
	html += '<div class="card">';
	html += '  <div class="card-body p-3">';
	html += '    <div class="small d-flex justify-content-between mb-2"><span class="text-muted">주문 상품</span><span><b>' + count + '개</b> (렌탈 ' + rent_count + ' / 일시불 ' + buy_count + ')</span></div>';
	if (rent_count > 0) {
		html += '    <div class="small d-flex justify-content-between mb-1"><span class="text-muted">등록비 합계</span><span>' + number_format(total_setup) + '원</span></div>';
		html += '    <div class="small d-flex justify-content-between mb-1"><span class="text-muted">월 렌탈료 합계</span><span>' + number_format(total_monthly_rent) + '원/월</span></div>';
		html += '    <div class="small d-flex justify-content-between mb-1"><span class="text-muted">약정 총 렌탈료</span><span>' + number_format(total_contract_rent) + '원</span></div>';
	}
	if (buy_count > 0) {
		html += '    <div class="small d-flex justify-content-between mb-1"><span class="text-muted">일시불 합계</span><span>' + number_format(total_buy) + '원</span></div>';
	}
	html += '    <div class="fw-bold fs-6 d-flex justify-content-between border-top pt-2 mt-1"><span>총 납부 예상액</span><span class="text-primary">' + number_format(total_pay) + '원</span></div>';
	html += '  </div>';
	html += '</div>';

	$('#order_summary').html(html);
}

/* =============================================================
 * 이벤트 핸들러
 * ============================================================= */
$(document).on('click', '.category-tab', function() {
	active_category = $(this).data('cat');
	render_category_tabs(all_products);
	render_modal_list(filter_products());
});

$('#product-search-input').on('input', function() {
	if (all_products.length > 0) render_modal_list(filter_products());
});

$(document).on('click', '.payment-toggle', function() {
	var uid  = $(this).data('model-uid');
	var type = $(this).data('type');
	if (!selected_models[uid]) return;
	selected_models[uid].payment_type = type;
	render_selected_model_card(uid);
	render_order_summary();
});

$(document).on('change', '.visit-cycle-select', function() {
	var uid = $(this).data('model-uid');
	if (!selected_models[uid]) return;
	selected_models[uid].visit_cycle = String($(this).val());
	render_selected_model_card(uid);
	render_order_summary();
});

$(document).on('change', '.duty-year-select', function() {
	var uid = $(this).data('model-uid');
	if (!selected_models[uid]) return;
	selected_models[uid].duty_year = String($(this).val());
	render_selected_model_card(uid);
	render_order_summary();
});

$(document).on('change', '.promo-check', function() {
	var uid  = $(this).data('model-uid');
	var code = $(this).val();
	if (!selected_models[uid] || !selected_models[uid].promotions.hasOwnProperty(code)) return;
	selected_models[uid].promotions[code] = $(this).is(':checked');
	render_selected_model_card(uid);
	render_order_summary();
});

/* =============================================================
 * 유틸
 * ============================================================= */
function escape_html(str) {
	return String(str || '')
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#39;');
}

function number_format(num) {
	return Number(num).toLocaleString('ko-KR');
}
</script>
