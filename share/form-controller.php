<?php
// test
/**
 * Builds search form UI
 *
 * The collection of functions that will builds search form UI.
 * Each function is separated by search kind (archive, terms, custom fields).
 *
 * @link https://fe-advanced-search.com/manual/make-search-form/
 *
 * @package    WordPress
 * @subpackage FE Advanced Search
 * @since      1.0.0
 * @version    2.1
 * @author     FirstElement,Inc.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Collects each form part and builds the complete search form.
 *
 * @param int     id          The form id of the search form that is building.
 * @param boolean shortcode_f Whether return for shortcode or echo the search form.
 * @since 1.0.0
 */
function create_searchform( $id = null, $shortcode_f = null ) {

	global $wpdb, $cols, $feadvns_max_line, $manag_no, $feadvns_search_b_label, $use_style_key, $style_body_key, $feadvns_search_target, $feadvns_reset_btn_sw, $feadvns_reset_btn_js, $feadvns_reset_btn_position, $feadvns_reset_btn_text;

	if ( is_admin() )
		return;

	// FEAS用スクリプト
	wp_enqueue_script( 'feas', plugins_url() . '/' . str_replace( basename( __FILE__ ), "", plugin_basename( __FILE__ ) ) . 'feas.js', array( 'jquery', 'wp-hooks' ), '1.0', true );
	// ajax_filtering用スクリプト
	wp_enqueue_script( 'ajax_filtering', plugins_url() . '/' . str_replace( basename( __FILE__ ), "", plugin_basename( __FILE__ ) ) . 'ajax_filtering.js', array( 'jquery', 'wp-hooks' ), '1.1', true );

	// FEAS用CSS
	wp_enqueue_style( 'feas', plugins_url() . '/' . str_replace( basename( __FILE__ ), "", plugin_basename( __FILE__ ) ) . 'feas.css' );

	if ( $id != null && is_numeric( $id ) ) {
		$manag_no = (int) $id;
	} else {
		$manag_no = 0;
	}

	$home_url = '/';

	// Polylang
	$lang = get_query_var( 'lang' );
	if ( $lang ) {
		$home_url = '/' . $lang . '/';
	}

	if ( is_ssl() ) {
		$action_url = home_url( $home_url, 'https' );
	} else {
		$action_url = home_url( $home_url );
	}

	/**
	 *
	 * フォームのactionにかけるフック
	 *
	 */
	$args = array(
		'manag_no' => (int) $manag_no,
	);
	$action_url = apply_filters( 'feas_form_action', $action_url, $args );

	/**
	 *
	 * フォームにインラインCSSを差し込むためのフック
	 *
	 */
	$inline_style = '';
	$args = array(
		'manag_no' => (int) $manag_no,
	);
	$inline_style = apply_filters( 'feas_form_inline_style', $inline_style, $args );
	if ( $inline_style ) {
		$inline_style = esc_attr( $inline_style );
		$inline_style = " style='{$inline_style}'";
	}

	/**
	 *
	 * フォームのattrにかけるフィルター
	 *
	 */
	$attr = '';
	$args = array(
		'manag_no' => (int) $manag_no,
	);
	$attr = apply_filters( 'feas_form_attr', $attr, $args );

	$output_form = "<form id='feas-searchform-{$manag_no}' action='{$action_url}' method='get' {$inline_style} {$attr}>\n";

	// 保存データ取得
	$get_data = get_db_save_data();

	// 取得データを並び替え
	$get_data = sort_db_save_data( $get_data );

	// 表示した場合チェックを入れる
	$ele_disp = null;

	// 対象投稿タイプをセットしていないとフォームを作らない
	$target_pt = get_option( $feadvns_search_target . $manag_no );

	if ( isset( $target_pt ) ) {

		for ( $i_gd = 0, $cnt_gd = count( $get_data ); $i_gd < $cnt_gd; $i_gd++ ) {

			$html = '';

			// 表示するかしないか取得
			if ( isset( $get_data[$i_gd][$cols[1]] ) && $get_data[$i_gd][$cols[1]] != 1 ) {

				// 前に挿入を取得
				if ( isset( $get_data[$i_gd][$cols[7]] ) && $get_data[$i_gd][$cols[7]] != null ) {
					$html .= str_replace( '\\', '', $get_data[$i_gd][$cols[7]] ) . "\n";
				}

				// ラベル取得
				if ( isset( $get_data[$i_gd][$cols[3]]) && $get_data[$i_gd][$cols[3]] != null ) {
					//$output_form .= "<div class='feas-item-header'>";
					$html .= str_replace( '\\', '', $get_data[$i_gd][$cols[3]] ) ."\n";
					//$output_form .= "</div>\n";
				}

				// エレメント取得
				$html .= create_element( $get_data[$i_gd], $i_gd );

				// 後に挿入を取得
				if ( isset( $get_data[$i_gd][$cols[8]] ) && $get_data[$i_gd][$cols[8]] != null ) {
					$html .= str_replace( '\\', '', $get_data[$i_gd][$cols[8]] ) . "\n";
				}

				/**
				 *
				 * 各検索項目のHTMLにかかるフック
				 *
				 */
				$html = apply_filters( 'feas_form_part_after_html', $html, (int) $manag_no, $i_gd );

				$output_form .= $html;

				// 表示した場合は
				$ele_disp = "disp";
			}
		}
	}

	if ( null != $ele_disp ) {

		// 検索ボタンのラベル取得
		$s_b_label = "検　索";
		$get_data = get_option( $feadvns_search_b_label . $manag_no );

		if ( isset( $get_data ) && null != $get_data ) {
			$s_b_label = $get_data;
		}

		/**
		 * リセットボタン
		 */

		$resetBtnBefore = $resetBtnAfter  = '';

		$reset_btn_sw = get_option( $feadvns_reset_btn_sw . $manag_no );
		if ( $reset_btn_sw ) {

			// JavaScriptによる全項目解除
			$onClickEvent = '';
			$reset_btn_js = get_option( $feadvns_reset_btn_js . $manag_no );
			if ( $reset_btn_js ) {
				$onClickEvent = 'onClick="feas_clear_form(' . $manag_no . ')" ';
			}
			// ボタンの文字列
			$reset_btn_text = get_option( $feadvns_reset_btn_text . $manag_no );
			if ( ! $reset_btn_text ) {
				$reset_btn_text = 'リセット';
			}

			$btnHtml = "<input type='reset' value='{$reset_btn_text}' {$onClickEvent}/>\n";

			// 表示位置
			$reset_btn_position = get_option( $feadvns_reset_btn_position . $manag_no );
			if ( '0' === $reset_btn_position ) {
				$resetBtnBefore = $btnHtml;
			} else {
				$resetBtnAfter = $btnHtml;
			}
		}

		// 前に挿入を取得
		$before_btn   = get_option( $feadvns_search_b_label . $manag_no . "_before" );
		$output_form .= str_replace( '\\', '', $before_btn ) . "\n";

		$output_form .= $resetBtnBefore;

		$output_form .= "<input type='submit' name='searchbutton' id='feas-submit-button-" . esc_attr( $manag_no ) . "' class='feas-submit-button' value='" . esc_attr( $s_b_label ) . "' />\n";

		$output_form .= $resetBtnAfter;

		// 後に挿入を取得
		$after_btn    = get_option( $feadvns_search_b_label . $manag_no . "_after" );
		$output_form .= str_replace( '\\', '', $after_btn ) . "\n";
	}

	$output_form .= "<input type='hidden' name='csp' value='search_add' />\n";
	$output_form .= "<input type='hidden' name='" . esc_attr( $feadvns_max_line . $manag_no ) . "' value='" . esc_attr( get_option( $feadvns_max_line . $manag_no ) ) . "' />\n";

	if ( isset( $chi_manag_no ) && ( $chi_manag_no != 0 ) ) {
		$output_form .= "<input type='hidden' name='fe_form_no' value='" . esc_attr( $chi_manag_no ) . "' />\n";
	} else {
		$output_form .= "<input type='hidden' name='fe_form_no' value='" . esc_attr( $manag_no ) . "' />\n";
	}

	/**
	 *
	 * 検索パーツ類の後に他のプログラムによって差し込まれる処理用のフック（hiddenタグの挿入など）
	 *
	 */
	$output_form = apply_filters( 'feas_form_after_parts', $output_form, (int) $manag_no );

	$output_form .= "</form>\n";

	/**
	 *
	 * 検索フォームの後に他のプログラムによって差し込まれる処理用のフック
	 *
	 */
	$output_form = apply_filters( 'feas_form_after_form', $output_form, (int) $manag_no );

	if ( null == $shortcode_f ) {
		echo $output_form;
	} else {
		return $output_form;
	}
}

/*============================
	検索フォームを作成
 ============================*/
function create_element( $get_data = array(), $number = 0 ) {
	global $wpdb, $cols;

	// 表示しないの場合は処理しない
	if ( $get_data[$cols[1]] == 1 )
		return null;

	// 形式 - なし の場合も処理しない
	if ( ! $get_data[$cols[4]] )
		return null;

	// 並び順を取得する
	$option_order = null;

	// エレメント作成
	if ( "archive" == $get_data[$cols[2]] ) {

		$ret_ele = create_archive_element( $get_data, $number );

	} elseif ( "meta_" == mb_substr( $get_data[$cols[2]], 0, 5 ) ) {

		$ret_ele = create_meta_element( $get_data, $number );

	} elseif ( "sel_tag" == $get_data[$cols[2]] ) {

		$ret_ele = create_tag_element( $get_data, $number );

	} else {
		$ret_ele = create_category_element( $get_data, $number );

	}
	return $ret_ele;
}

/*============================
	アーカイブ（archive）のエレメント作成
 ============================*/
function create_archive_element( $get_data, $number ) {
	global $wpdb, $cols, $manag_no, $feadvns_search_target, $feadvns_show_count, $feadvns_include_sticky, $feadvns_exclude_id, $feadvns_default_cat, $wp_locale, $feadvns_default_page, $feadvns_exclude_term_id;

	$nocnt = false;
	$lang_id = $ret_ele = $showcnt =  null;
	$get_cond = $target_pt = $exclude_post_ids = $polylang_sql = '';
	$sp = $get_archive = array();

	// 検索方法 - 0=年, 1=年月, 2=年月日
	$search_type = $get_data[$cols[43]];

	if ( false === $search_type || '' === $search_type ) {
		$search_type = '1';
	}

	// 更新日で検索
	$search_by_modified = $get_data[$cols[44]];

	// テキスト入力で検索
	$search_by_text = $get_data[$cols[45]];

	// DatePickerを使用
	$search_with_dp = $get_data[$cols[46]];

	// 開始日
	$search_dp_limit_start = $get_data[$cols[47]];

	// 終了日
	$search_dp_limit_end = $get_data[$cols[48]];

	// 日付フォーマット
	$search_date_format= $get_data[$cols[49]];


	// 検索対象のpost_typeを取得
	$target_pt_tmp = get_option( $feadvns_search_target . $manag_no );
	if ( $target_pt_tmp ) {
		$target_pt = "'" . implode( "','", (array) $target_pt_tmp ) . "'";
	} else {
		$target_pt = "'post'";
	}

	// 投稿ステータス
	if ( in_array( 'attachment', (array) $target_pt_tmp ) ) {
		$post_status = "'publish', 'inherit'";
	} else {
		$post_status = "'publish'";
	}

	// 固定記事(Sticky Posts)を検索対象から省く設定の場合、カウントに含めない
	$target_sp = get_option( $feadvns_include_sticky . $manag_no );
	if ( 'yes' != $target_sp ) {

		$sticky = get_option( 'sticky_posts' );

		// Post Typeの除外IDにマージ
		if ( $sticky != array() ) {
			$sp = array_merge( $sp, $sticky );
		}
	}

	// 固定条件 > タクソノミ／ターム
	$fixed_term = get_option( $feadvns_default_cat . $manag_no );

	// 固定条件 > 親ページ
	$default_page = $pwhere = '';
	$default_page = get_option( $feadvns_default_page . $manag_no );
	if ( $default_page ) {
		$default_page = implode( ',', (array) $default_page );
		$default_page = " AND p.post_parent IN (" . esc_sql( $default_page ) . ")";
	}

	// 検索条件に件数を表示
	$showcnt = get_option( $feadvns_show_count . $manag_no );

	// 除外する記事ID
	$exclude_id = get_option( $feadvns_exclude_id . $manag_no );
	if ( $exclude_id ) {
		$sp = array_merge( $sp, $exclude_id ); // 除外IDにマージ
	}

	// 検索結果から除外するタームID（全体）
	// タームごとのカウントに反映するため
	$exclude_term_id = get_option( $feadvns_exclude_term_id . $manag_no );
	if ( $exclude_term_id ) {
		$args['cat']      = $exclude_term_id;
		$args['format']   = 'array';
		$args['mode']     = 'exclude';
		$dcat['orderby']  = '';
		$exclude_post_ids = create_where_single_cat( $args );
	}
	if ( $exclude_post_ids ) {
		$sp = array_merge( $sp, $exclude_post_ids ); // 除外IDにマージ
	}

	// 除外IDをカンマ区切りにする
	if ( $sp ) {
		$sp = implode( ',', $sp );
	}

	// 条件内の並び順
	$order_by = "value"; // ymは年と月を繋いだ値。例：201203
	if ( isset( $get_data[$cols[5]] ) ) {
		if ( '8' === $get_data[$cols[5]] || '9' === $get_data[$cols[5]] || 'a' === $get_data[$cols[5]] ) {
			$order_by = "value";
		}
	}

	// 条件内の並び順 昇順/降順
	$order = "ASC";
	if ( isset( $get_data[$cols[35]] ) ) {
		switch ( (string) $get_data[$cols[35]] ) {
			case '8':
			case 'asc':
				$order = "ASC";
				break;
			case '9':
			case 'desc':
				$order = "DESC";
				break;
			default:
				$order = "ASC";
				break;
		}
	}

	// 「要素内の並び順」が「自由記述」の場合は、ターム一覧をDBから呼び出す代わりに記述内容で配列get_catsを構成
	if ( 'b' === $get_data[$cols[5]] ) {

		$options = $get_data[$cols[36]];

		if ( ! empty( $options ) ) {

			$get_archive = array();

			// 行数分ループを回す
			for ( $i = 0; $cnt = count( $options ), $i < $cnt; $i++ ) {

				if ( empty( $options[$i] ) )
					continue;

				$get_archive[$i] = new stdClass();

				// 値
				$get_archive[$i]->value = esc_sql( $options[$i]['value'] );

				// 表記
				$get_archive[$i]->text = $options[$i]['text'];

				// 階層
				$get_archive[$i]->depth = $options[$i]['depth'];
			}
		}
	}

	// 「自由記述」以外
	else {

		// キャッシュ
		if ( false === ( $get_archive = feas_cache_judgment( $manag_no, 'archive', $number ) ) ) {

			$sql = '';
			$target = 'post_date';
			if ( $search_by_modified ) {
				$target = 'post_modified';
			}
			if ( '0' === $search_type ) {
				$sql  = " SELECT DISTINCT YEAR( {$target} ) AS `value`";
			} else if ( '1' === $search_type ) {
				$sql  = " SELECT DISTINCT YEAR( {$target} ) AS `year`, MONTH( {$target} ) AS `month`, CONCAT( YEAR( {$target} ), '-', LPAD( MONTH( {$target} ), 2, '0' ) ) AS `value` ";
			} else if ( '2' === $search_type ) {
				$sql  = " SELECT DISTINCT YEAR( {$target} ) AS `year`, MONTH( {$target} ) AS `month`, DAY( {$target} ) AS `day` , CONCAT( YEAR( {$target} ), '-', LPAD( MONTH( {$target} ), 2, '0' ), '-', LPAD( DAY( {$target} ), 2, '0' ) ) AS `value` ";
			}
			$sql .= " FROM {$wpdb->posts} AS p";
			$sql .= " WHERE 1=1";
			if ( $search_dp_limit_start ) {
				$sql .= " AND p.{$target} >= '" . esc_sql( $search_dp_limit_start ) . " 00:00:00'";
			}
			if ( $search_dp_limit_end ) {
				$sql .= " AND p.{$target} <= '" . esc_sql( $search_dp_limit_end ) . " 00:00:00'";
			}
			$sql .= " AND p.post_type IN( $target_pt )"; // ToDo: 他の条件と同じくすべての選択肢（=全期間）を表示すべきか
			$sql .= " AND p.post_status IN ( {$post_status} )";
			$sql .= " ORDER BY " . $order_by . " " . $order;

			$get_archive = $wpdb->get_results( $sql );

			feas_cache_create( $manag_no, 'archive', $number, $get_archive );
		}
	}

	$cnt_arc = count( $get_archive );

	// Polylang
	if ( in_array( 'polylang/polylang.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

		$polylang = get_option( 'polylang' );
		if ( $polylang ) {
			$lang = $polylang['default_lang'];
		}

		$langVar = get_query_var( 'lang' );
		if ( $langVar ) {
			$lang = $langVar;
		}

		if ( $lang ) {
			$sql = <<<SQL
SELECT tt.term_id
FROM {$wpdb->term_taxonomy} AS tt
LEFT JOIN {$wpdb->terms} AS t
ON tt.term_id = t.term_id
WHERE t.slug = %s
LIMIT 1
SQL;
			$sql = $wpdb->prepare( $sql, $lang );
			$lang_id = $wpdb->get_var( $sql );
		}

		if ( NULL !== $lang_id ) {
			$sql = " AND tr2.term_taxonomy_id IN ( %d )";
			$polylang_sql = $wpdb->prepare( $sql, $lang_id );
		}
	}

	// WPML
	$wpml_lang = $wpml_sql = NULL;
	if ( in_array( 'sitepress-multilingual-cms/sitepress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		$wpml_lang = apply_filters( 'wpml_current_language', NULL );
		if ( $wpml_lang ) {
			$add_sql = " AND wpml_translations.language_code = %s ";
			$wpml_sql = $wpdb->prepare( $add_sql, $wpml_lang );
		}
	}

	// 件数を取得してキャッシュ保存
	if ( $get_archive ) {
		$archive_cnt = array();
		foreach( $get_archive as $archive_ym ) {
			if ( false === ( $cnt = feas_cache_judgment( $manag_no, 'arc_cnt_' . $archive_ym->value, false ) ) ) {
				$sql  = " SELECT count( DISTINCT p.ID ) AS cnt FROM {$wpdb->posts} AS p";
				if ( $fixed_term ) {
					$sql .= " INNER JOIN {$wpdb->term_relationships} AS tr ON p.ID = tr.object_id";
				}
				if ( $polylang_sql ) {
					$sql .= " INNER JOIN {$wpdb->term_relationships} AS tr2 ON p.ID = tr2.object_id";
				}
				if ( $wpml_sql ) {
					$sql .= " INNER JOIN {$wpdb->prefix}icl_translations AS wpml_translations ON p.ID = wpml_translations.element_id";
				}
				$sql .= " WHERE 1=1";
				if ( '0' === $search_type ) {
					$sql .= " AND YEAR( post_date ) = '{$archive_ym->value}'";
				} else if ( '1' === $search_type ) {
					$sql .= " AND CONCAT( YEAR( post_date ), '-', LPAD( MONTH( post_date ), 2, '0' ) ) = '{$archive_ym->value}'";
				} else if ( '2' === $search_type ) {
					$sql .= " AND CONCAT( YEAR( post_date ), '-', LPAD( MONTH( post_date ), 2, '0' ), '-', LPAD( DAY( post_date ), 2, '0' ) ) = '{$archive_ym->value}'";
				}

				if ( $sp ) {
					$sql .= " AND p.ID NOT IN ( $sp )";
				}
				if ( $default_page ) {
					$sql .= $default_page;
				}
				if ( $fixed_term ) {
					$sql .= " AND tr.term_taxonomy_id = " . esc_sql( $fixed_term );
				}
				if ( $polylang_sql ) {
					$sql .= $polylang_sql;
				}
				if ( $wpml_sql ) {
					$sql .= $wpml_sql;
				}
				$sql .= " AND p.post_type IN ( {$target_pt} )";
				$sql .= " AND p.post_status IN ( {$post_status} )";

				$cnt = $wpdb->get_row( $sql );
				feas_cache_create( $manag_no, 'arc_cnt_' . $archive_ym->value, false, $cnt );
			}
			$archive_cnt[] = $cnt;
		}
	}

	// 未選択時の文字列
	$noselect_text = $get_data[$cols[27]];

	// デフォルト値
	$default_value = $get_data[$cols[39]];
	if ( '' !== $default_value ) {
		$default_value = explode( ',', $default_value );
	}

	// テキストでアーカイブ検索
	if ( '1' === get_option( $cols[45] . $manag_no . "_" . $number ) ) {
		$get_data[$cols[4]] = 'search_by_text';
	}

	switch ( $get_data[$cols[4]] ) {

		/**
		 *	ドロップダウン
		 */
		case 1:
		case 'a':

			$ret_opt = '';

			for ( $i_arc = 0; $i_arc < $cnt_arc; $i_arc++ ) {

				// 0件タームは表示しない場合
				if ( $nocnt && $archive_cnt[$i_arc]->cnt == 0 )
					continue;

				$selected = '';
				if ( isset( $_GET['fe_form_no'] ) && $_GET['fe_form_no'] == $manag_no ) {
					if ( isset( $_GET['search_element_' . $number] ) ) {
						if ( $_GET['search_element_' . $number] == $get_archive[$i_arc]->value ) {
							$selected = ' selected ';
						}
					}
				} elseif ( $default_value ) {
					if ( is_array( $default_value ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( $default_value[$i_lists] == $get_archive[$i_arc]->value ) {
								$selected = ' selected';
							}
						}
					}
				}

				$arc_cnt = '';
				if ( 'yes' == $showcnt ) {
					$arc_cnt = " (" . $archive_cnt[$i_arc]->cnt. ") ";
				}

				$depth = '01';
				$indentSpace = '';

				// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassとインデントを準備
				if ( 'b' === $get_data[$cols[5]] ) {
					if ( '1' !== $get_archive[$i_arc]->depth ) {
						$depth = str_pad( $get_archive[$i_arc]->depth, 2, '0', STR_PAD_LEFT );
						for ( $i_depth = 1; $i_depth < $get_archive[$i_arc]->depth; $i_depth++ ) {
							$indentSpace .= '&nbsp;&nbsp;';
						}
					}
				}

				// Sanitaize
				$ret_id  = esc_attr( "feas_{$manag_no}_{$number}_{$i_arc}" );
				$ret_val = esc_attr( $get_archive[$i_arc]->value );

				// 自由記述
				if ( 'b' === $get_data[$cols[5]] ) {
					$ret_text = esc_html( $get_archive[$i_arc]->text . $arc_cnt );
				} else {
					// 年
					if ( '0' === $search_type ) {
						if ( ! $search_date_format ) {
							$search_date_format = 'Y年';
						}
						$ret_text = date_i18n( $search_date_format, strtotime($get_archive[$i_arc]->value . '-01-01') ) . $arc_cnt;

					} // 年月
					else if ( '1' === $search_type ) {
						if ( ! $search_date_format ) {
							$search_date_format = 'Y年n月';
						}
						$ret_text = date_i18n( $search_date_format, strtotime($get_archive[$i_arc]->year . '-' . $get_archive[$i_arc]->month .'-01') ) . $arc_cnt;

					} // 年月日
					else if ( '2' === $search_type ) {
						if ( ! $search_date_format ) {
							$search_date_format = 'Y年n月j日';
						}
						$ret_text = date_i18n( $search_date_format, strtotime($get_archive[$i_arc]->year . '-' . $get_archive[$i_arc]->month .'-' . $get_archive[$i_arc]->day ) ) . $arc_cnt;
					}
				}

				/**
				 *
				 * selectのclassにかけるフィルター
				 *
				 */
				$class = 'feas_clevel_' . esc_attr( $depth );
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_arc,
					'text'          => $ret_text,
					'value'         => esc_attr( $get_archive[$i_arc]->value ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $archive_cnt[$i_arc]->cnt,
				);
				$class = apply_filters( 'feas_archive_dropdown_class', $class, $args );

				/**
				 *
				 * selectのattrにかけるフィルター
				 *
				 */
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_arc,
					'class'         => $class,
					'text'          => $ret_text,
					'value'         => esc_attr( $get_archive[$i_arc]->value ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $archive_cnt[$i_arc]->cnt,
				);
				$attr = apply_filters( 'feas_archive_dropdown_attr', $attr, $args );

				$html  = "<option id='{$ret_id}' value='{$ret_val}' class='{$class}' {$attr} {$selected}>";
				$html .= $indentSpace . $ret_text;
				$html .= "</option>\n";

				/**
				 *
				 * 各オプションごとにかけるフィルター
				 *
				 */
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_arc,
					'class'         => $class,
					'attr'          => $attr,
					'text'          => $ret_text,
					'value'         => esc_attr( $get_archive[$i_arc]->value ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $archive_cnt[$i_arc]->cnt,
				);
				$html = apply_filters( 'feas_archive_dropdown_html', $html, $args );

				// ループ前段に結合
				$ret_opt .= $html;

			}

			/**
			 *
			 * selectのclassにかけるフィルター
			 *
			 */
			$class = 'feas_archive_dropdown';
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'ret_opt'       => $ret_opt,
				'show_post_cnt' => $showcnt,
			);
			$class = apply_filters( 'feas_archive_dropdown_group_class', $class, $args );

			/**
			 *
			 * selectのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'class'         => $class,
				'ret_opt'       => $ret_opt,
				'show_post_cnt' => $showcnt,
			);
			$attr = apply_filters( 'feas_archive_dropdown_group_attr', $attr, $args );

			// Sanitize
			$ret_name  = esc_attr( "search_element_{$number}" );
			$ret_id    = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_txt   = esc_html( $noselect_text );

			$html  = "<select name='{$ret_name}' id='{$ret_id}' class='{$class}' {$attr}>\n";
			$html .= "<option id='{$ret_id}_none' value=''>\n";
			$html .= $ret_txt;
			$html .= "</option>\n";
			$html .= $ret_opt;
			$html .= "</select>\n";

			/**
			 *
			 * select全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'class'         => $class,
				'attr'          => $attr,
				'ret_opt'       => $ret_opt,
				'show_post_cnt' => $showcnt,
			);
			$html = apply_filters( 'feas_archive_dropdown_group_html', $html, $args );

			// ループ前段に結合
			$ret_ele .= $html;

			break;

		/**
		 *	セレクトボックス
		 */
		case 2:
		case 'b':

			$ret_opt = '';
			$selected_cnt = 0;

			for ( $i_arc = 0, $cnt_arc = count( $get_archive ); $i_arc < $cnt_arc; $i_arc++ ) {

				// 0件タームは表示しない場合
				if ( $nocnt && $archive_cnt[$i_arc]->cnt == 0 )
					continue;

				$selected = '';
				if ( isset( $_GET['fe_form_no'] ) && $_GET['fe_form_no'] == $manag_no ) {
					if ( isset( $_GET["search_element_" . $number] ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $_GET["search_element_" . $number] ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( isset( $_GET["search_element_" . $number][$i_lists] ) ) {
								if ( $_GET["search_element_" . $number][$i_lists] == $get_archive[$i_arc]->value ) {
									$selected = ' selected';
									$selected_cnt++;
								}
							}
						}
					}
				} elseif ( $default_value ) {
					if ( is_array( $default_value ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( $default_value[$i_lists] == $get_archive[$i_arc]->value ) {
								$selected = ' selected';
							}
						}
					}
				}

				$arc_cnt = '';
				if ( 'yes' == $showcnt ) {
					$arc_cnt = " (" . $archive_cnt[$i_arc]->cnt. ") ";
				}

				$depth = '01';
				$indentSpace = '';

				// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassとインデントを準備
				if ( 'b' === $get_data[$cols[5]] ) {
					if ( '1' !== $get_archive[$i_arc]->depth ) {
						$depth = str_pad( $get_archive[$i_arc]->depth, 2, '0', STR_PAD_LEFT );
						for ( $i_depth = 1; $i_depth < $get_archive[$i_arc]->depth; $i_depth++ ) {
							$indentSpace .= '&nbsp;&nbsp;';
						}
					}
				}

				// Sanitize
				$ret_id   = esc_attr( "feas_{$manag_no}_{$number}_{$i_arc}" );
				$ret_val  = esc_attr( $get_archive[$i_arc]->value );

				// 自由記述
				if ( 'b' === $get_data[$cols[5]] ) {
					$ret_text = esc_html( $get_archive[$i_arc]->text . $arc_cnt );
				} else {
					// 年
					if ( '0' === $search_type ) {
						if ( ! $search_date_format ) {
							$search_date_format = 'Y年';
						}
						$ret_text = date_i18n( $search_date_format, strtotime($get_archive[$i_arc]->value . '-01-01') ) . $arc_cnt;

					} // 年月
					else if ( '1' === $search_type ) {
						if ( ! $search_date_format ) {
							$search_date_format = 'Y年n月';
						}
						$ret_text = date_i18n( $search_date_format, strtotime($get_archive[$i_arc]->year . '-' . $get_archive[$i_arc]->month .'-01') ) . $arc_cnt;

					} // 年月日
					else if ( '2' === $search_type ) {
						if ( ! $search_date_format ) {
							$search_date_format = 'Y年n月j日';
						}
						$ret_text = date_i18n( $search_date_format, strtotime($get_archive[$i_arc]->year . '-' . $get_archive[$i_arc]->month .'-' . $get_archive[$i_arc]->day ) ) . $arc_cnt;
					}
				}

				/**
				 *
				 * selectのclassにかけるフィルター
				 *
				 */
				$class = 'feas_clevel_' . esc_attr( $depth );
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_arc,
					'text'          => $ret_text,
					'value'         => esc_attr( $get_archive[$i_arc]->value ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $archive_cnt[$i_arc]->cnt,
				);
				$class = apply_filters( 'feas_archive_multiple_class', $class, $args );

				/**
				 *
				 * 各optionのattrにかけるフィルター
				 *
				 */
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_arc,
					'class'         => $class,
					'text'          => $ret_text,
					'value'         => esc_attr( $get_archive[$i_arc]->value ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $archive_cnt[$i_arc]->cnt,
				);
				$attr = apply_filters( 'feas_archive_multiple_attr', $attr, $args );

				$html  = "<option id='{$ret_id}' value='{$ret_val}' class='{$class}' {$attr} {$selected}>";
				$html .= $indentSpace . $ret_text;
				$html .= "</option>\n";

				/**
				 *
				 * 各オプションごとにかけるフィルター
				 *
				 */
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_arc,
					'class'         => $class,
					'attr'          => $attr,
					'text'          => $ret_text,
					'value'         => esc_attr( $get_archive[$i_arc]->value ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $archive_cnt[$i_arc]->cnt,
				);
				$html = apply_filters( 'feas_archive_multiple_html', $html, $args );

				// ループ前段に結合
				$ret_opt .= $html;
			}

			// iOSではセレクトボックスが1行にまとめられてしまい、selectedが1件も付いていないと「0項目」と表示されてしまい、未選択時テキストが表示されないため。
			$selected = '';
			if ( 0 === $selected_cnt ) {
				if ( wp_is_mobile() ) {
					$selected = ' selected';
				}
			}

			/**
			 *
			 * selectのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'ret_opt'       => $ret_opt,
				'show_post_cnt' => $showcnt,
			);
			$attr = apply_filters( 'feas_archive_multiple_group_attr', $attr, $args );

			// Sanitize
			$ret_name  = esc_attr( "search_element_{$number}[]" );
			$ret_id    = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_txt   = esc_html( $noselect_text );

			$html  = "<select name='{$ret_name}' id='{$ret_id}' size='5' multiple='multiple' {$attr}>\n";
			$html .= "<option id='{$ret_id}_none' value='' {$selected}>";
			$html .= $ret_txt;
			$html .= "</option>\n";
			$html .= $ret_opt;
			$html .= "</select>\n";

			/**
			 *
			 * セレクトボックス全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'ret_opt'       => $ret_opt,
				'show_post_cnt' => $showcnt,
			);
			$html = apply_filters( 'feas_archive_multiple_group_html', $html, $args );

			// ループ前段に結合
			$ret_ele .= $html;

			break;

		/**
		 *	チェックボックス
		 */
		case 3:
		case 'c':

			for ( $i_arc = 0, $cnt_arc = count( $get_archive ); $i_arc < $cnt_arc; $i_arc++ ) {

				// 0件タームは表示しない場合
				if ( $nocnt && $archive_cnt[$i_arc]->cnt == 0 )
					continue;

				$checked = '';
				if ( isset( $_GET['fe_form_no'] ) && $_GET['fe_form_no'] == $manag_no ) {
					if ( isset( $_GET["search_element_" . $number] ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $_GET["search_element_" . $number] ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( isset( $_GET["search_element_" . $number][$i_lists] ) ) {
								if ( $_GET["search_element_" . $number][$i_lists] == $get_archive[$i_arc]->value ) {
									$checked = ' checked';
								}
							}
						}
					}
				} elseif ( $default_value ) {
					if ( is_array( $default_value ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( $default_value[$i_lists] == $get_archive[$i_arc]->value ) {
								$checked = ' checked';
							}
						}
					}
				}

				$arc_cnt = '';
				if ( 'yes' == $showcnt ) {
					$arc_cnt = " (" . $archive_cnt[$i_arc]->cnt. ") ";
				}

				$depth = '01';

				// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassを準備
				if ( 'b' === $get_data[$cols[5]] ) {
					$depth = str_pad( $get_archive[$i_arc]->depth, 2, '0', STR_PAD_LEFT );
				}

				// Sanitize
				$ret_id   = esc_attr( "feas_{$manag_no}_{$number}_{$i_arc}" );
				$ret_name = esc_attr( "search_element_{$number}[]" );
				$ret_val  = esc_attr( $get_archive[$i_arc]->value );

				if ( 'b' === $get_data[$cols[5]] ) {
					$ret_text = esc_html( $get_archive[$i_arc]->text . $arc_cnt );
				} else {
					// 年
					if ( '0' === $search_type ) {
						if ( ! $search_date_format ) {
							$search_date_format = 'Y年';
						}
						$ret_text = date_i18n( $search_date_format, strtotime( $get_archive[$i_arc]->value . '-01-01' ) ) . $arc_cnt;

					} // 年月
					else if ( '1' === $search_type ) {
						if ( ! $search_date_format ) {
							$search_date_format = 'Y年n月';
						}
						$ret_text = date_i18n( $search_date_format, strtotime( $get_archive[$i_arc]->year . '-' . $get_archive[$i_arc]->month .'-01' ) ) . $arc_cnt;

					} // 年月日
					else if ( '2' === $search_type ) {
						if ( ! $search_date_format ) {
							$search_date_format = 'Y年n月j日';
						}
						$ret_text = date_i18n( $search_date_format, strtotime( $get_archive[$i_arc]->year . '-' . $get_archive[$i_arc]->month .'-' . $get_archive[$i_arc]->day ) ) . $arc_cnt;
					}
				}

				/**
				 *
				 * チェックボックスのclassにかけるフィルター
				 *
				 */
				$class = 'feas_clevel_' . esc_attr( $depth );
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_arc,
					'text'          => esc_attr( $ret_text ),
					'value'         => esc_attr( $get_archive[$i_arc]->value ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $archive_cnt[$i_arc]->cnt,
				);
				$class = apply_filters( 'feas_archive_checkbox_class', $class, $args );

				/**
				 *
				 * チェックボックスのattrにかけるフィルター
				 *
				 */
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_arc,
					'class'         => $class,
					'text'          => esc_attr( $ret_text ),
					'value'         => esc_attr( $get_archive[$i_arc]->value ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $archive_cnt[$i_arc]->cnt,
				);
				$attr = apply_filters( 'feas_archive_checkbox_attr', $attr, $args );

				$html  = "<label for='{$ret_id}' class='{$class}'>";
				$html .= "<input id='{$ret_id}' type='checkbox' name='{$ret_name}' value='{$ret_val}' {$attr} {$checked} />";
				$html .= "<span>{$ret_text}</span>";
				$html .= "</label>\n";

				/**
				 *
				 * 各チェックボックスごとにかけるフィルター
				 *
				 */
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_arc,
					'class'         => $class,
					'attr'          => $attr,
					'text'          => esc_attr( $ret_text ),
					'value'         => esc_attr( $get_archive[$i_arc]->value ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $archive_cnt[$i_arc]->cnt,
				);
				$html = apply_filters( 'feas_archive_checkbox_html', $html, $args );

				// ループ前段に結合
				$ret_ele .= $html;

			}

			/**
			 *
			 * チェックボックスグループ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'ret_ele'       => $ret_ele,
				'show_post_cnt' => $showcnt,
			);
			$ret_ele = apply_filters( 'feas_archive_checkbox_group_html', $ret_ele, $args );

			break;

		/**
		 *	ラジオボタン
		 */
		case 4:
		case 'd':

			/**
			 *	ラジオボタンの「未選択」の表示/非表示
			 */
			$noselect_status = get_option( $cols[31] . $manag_no . '_' . $number );
			if ( $noselect_status ) {

				$ret_ele .= "<label for='feas_" . esc_attr( $manag_no . "_" . $number ) . "_none' class='feas_clevel_01'>";
				$ret_ele .= "<input id='feas_" . esc_attr( $manag_no . "_" . $number ) . "_none' type='radio' name='search_element_" . esc_attr( $number ) . "' value='' />";
				$ret_ele .= "<span>" . esc_html( $noselect_text ) . "</span>";
				$ret_ele .= "</label>\n";
			}

			for ( $i_arc = 0, $cnt_arc = count( $get_archive ); $i_arc < $cnt_arc; $i_arc++ ) {

				// 0件タームは表示しない場合
				if ( $nocnt && $archive_cnt[$i_arc]->cnt == 0 )
					continue;

				$checked = '';
				if ( isset( $_GET['fe_form_no'] ) && $_GET['fe_form_no'] == $manag_no ) {
					if ( isset( $_GET['search_element_' .$number] ) ) {
						if ( $_GET['search_element_' . $number] == $get_archive[$i_arc]->value ) {
							$checked = ' checked';
						}
					}
				} elseif ( $default_value ) {
					if ( is_array( $default_value ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( $default_value[$i_lists] == $get_archive[$i_arc]->value ) {
								$checked = ' checked';
							}
						}
					}
				}

				$arc_cnt = '';
				if ( 'yes' == $showcnt ) {
					$arc_cnt = " (" . $archive_cnt[$i_arc]->cnt. ") ";
				}

				$depth = '01';

				// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassを準備
				if ( 'b' === $get_data[$cols[5]] ) {
					$depth = str_pad( $get_archive[$i_arc]->depth, 2, '0', STR_PAD_LEFT );
				}

				// Sanitize
				$ret_id   = esc_attr( "feas_{$manag_no}_{$number}_{$i_arc}" );
				$ret_name = esc_attr( "search_element_{$number}" );
				$ret_val  = esc_attr( $get_archive[$i_arc]->value );

				if ( 'b' === $get_data[$cols[5]] ) {
					$ret_text = esc_html( $get_archive[$i_arc]->text . $arc_cnt );
				} else {
					// 年
					if ( '0' === $search_type ) {
						if ( ! $search_date_format ) {
							$search_date_format = 'Y年';
						}
						$ret_text = date_i18n( $search_date_format, strtotime( $get_archive[$i_arc]->value . '-01-01' ) ) . $arc_cnt;

					} // 年月
					else if ( '1' === $search_type ) {
						if ( ! $search_date_format ) {
							$search_date_format = 'Y年n月';
						}
						$ret_text = date_i18n( $search_date_format, strtotime( $get_archive[$i_arc]->year . '-' . $get_archive[$i_arc]->month .'-01' ) ) . $arc_cnt;

					} // 年月日
					else if ( '2' === $search_type ) {
						if ( ! $search_date_format ) {
							$search_date_format = 'Y年n月j日';
						}
						$ret_text = date_i18n( $search_date_format, strtotime( $get_archive[$i_arc]->year . '-' . $get_archive[$i_arc]->month .'-' . $get_archive[$i_arc]->day ) ) . $arc_cnt;
					}
				}

				/**
				 *
				 * ラジオボタンのclassにかけるフィルター
				 *
				 */
				$class = 'feas_clevel_' . esc_attr( $depth );
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_arc,
					'text'          => $ret_text,
					'value'         => esc_attr( $get_archive[$i_arc]->value ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $archive_cnt[$i_arc]->cnt,
				);
				$class = apply_filters( 'feas_archive_radio_class', $class, $args );

				/**
				 *
				 * 各ラジオボタンのattrにかけるフィルター
				 *
				 */
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_arc,
					'class'         => $class,
					'text'          => $ret_text,
					'value'         => esc_attr( $get_archive[$i_arc]->value ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $archive_cnt[$i_arc]->cnt,
				);
				$attr = apply_filters( 'feas_archive_radio_attr', $attr, $args );

				$html  = "<label for='{$ret_id}' class='{$class}'>";
				$html .= "<input id='{$ret_id}' type='radio' name='{$ret_name}' value='{$ret_val}' {$attr} {$checked} />";
				$html .= "<span>{$ret_text}</span>";
				$html .= "</label>\n";

				/**
				 *
				 * 各ラジオボタンごとにかけるフィルター
				 *
				 */
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_arc,
					'class'         => $class,
					'attr'          => $attr,
					'text'          => esc_attr( $ret_text ),
					'value'         => esc_attr( $get_archive[$i_arc]->value ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $archive_cnt[$i_arc]->cnt,
				);
				$html = apply_filters( 'feas_archive_radio_html', $html, $args );

				// ループ前段に結合
				$ret_ele .= $html;

			}

			/**
			 *
			 * ラジオボタングループ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'ret_ele'       => $ret_ele,
				'show_post_cnt' => $showcnt,
			);
			$ret_ele = apply_filters( 'feas_archive_radio_group_html', $ret_ele, $args );

			break;

		/**
		 *	フリーワード
		 */
		case 5:
		case 'e':

			$placeholder_data = '';
			$placeholder = '';
			$output_js = '';

			$placeholder_data = $get_data[$cols[30]];
			if ( $placeholder_data ) {
				$placeholder = ' placeholder="' . esc_attr( $placeholder_data ) . '"';
				$output_js = '<script>jQuery("#feas_' . esc_attr( $manag_no . '_' . $number ) . '").focus( function() { jQuery(this).attr("placeholder",""); }).blur( function() {
    jQuery(this).attr("placeholder", "' . esc_attr( $placeholder_data ) . '"); });</script>';
			}

			$s_keyword = '';
			if ( isset( $_GET['fe_form_no'] ) && $manag_no == $_GET['fe_form_no'] ) {
				if ( isset( $_GET['s_keyword_' . $number] ) ) {
					$s_keyword = $_GET['s_keyword_' . $number];
				}
			} elseif ( $default_value ) {
				if ( is_array( $default_value ) ) {
					for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
						if ( '' !== $s_keyword ) {
							$s_keyword .= ' ';
						}
						$s_keyword .= $default_value[$i_lists];
					}
				}
			}

			/**
			 *
			 * inputのclassにかけるフィルター
			 *
			 */
			$class = 'feas_archive_freeword';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$class = apply_filters( 'feas_archive_freeword_class', $class, $args );

			/**
			 *
			 * inputのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$attr = apply_filters( 'feas_archive_freeword_attr', $attr, $args );

			// Sanitize
			$ret_id   = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_name = esc_attr( "s_keyword_{$number}" );
			$ret_val  = esc_attr( stripslashes( $s_keyword ) );

			$html  = "<input type='text' name='{$ret_name}' id='{$ret_id}' class='{$class}' value='{$ret_val}' {$placeholder} {$attr} />";
			$html .= $output_js;

			/**
			 *
			 * AND/ORオプション
			 *
			 */
			$andor_html = '';
			$andor_ui_flag = $get_data[$cols[6]];

			if ( 'c' === $andor_ui_flag ) {

				// Sanitize
				$ret_6_id    = esc_attr( "feas_{$manag_no}_{$number}_andor" );
				$ret_6_name  = esc_attr( "feas_andor_{$number}" );

				/**
				 * Filter for class
				 */
				$ret_6_class = 'feas_freeword_andor';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_6_class = esc_attr( apply_filters( 'feas_freeword_andor_class', $ret_6_class, $args ) );

				/**
				 * Filter apply to the text "Exclude"
				 */
				$ret_6_or_text = 'OR';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_6_or_text  = esc_html( apply_filters( 'feas_freeword_andor_or_text', $ret_6_or_text, $args ) );

				$ret_6_and_text = 'AND';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_6_and_text = esc_html( apply_filters( 'feas_freeword_andor_and_text', $ret_6_and_text, $args ) );

				$checked_0 = $checked_1 = '';
				if ( isset( $_GET["{$ret_6_name}"] ) && 'a' === $_GET["{$ret_6_name}"] ) {
					$checked_0 = 'checked';
				} else {
					$checked_1 = 'checked';
				}

				$andor_html  = "<label for='{$ret_6_id}_0' class='{$ret_6_class}'>";
				$andor_html .= "<input type='radio' id='{$ret_6_id}_0' name='{$ret_6_name}' value='a' {$checked_0} />";
				$andor_html .= $ret_6_or_text;
				$andor_html .= "</label>";
				$andor_html .= "<label for='{$ret_6_id}_1' class='{$ret_6_class}'>";
				$andor_html .= "<input type='radio' id='{$ret_6_id}_1' name='{$ret_6_name}' value='b' {$checked_1} />";
				$andor_html .= $ret_6_and_text;
				$andor_html .= "</label>";
			}

			/**
			 *
			 * 除外オプション
			 *
			 */
			$exclude_html = '';
			$exclude_ui_flag = $get_data[$cols[52]];

			if ( '2' === $exclude_ui_flag ) {

				// Sanitize
				$ret_52_id    = esc_attr( "feas_{$manag_no}_{$number}_exclude" );
				$ret_52_name  = esc_attr( "feas_exclude_{$number}" );

				/**
				 * Filter for class
				 */
				$ret_52_class = 'feas_freeword_exclude';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_52_class = esc_attr( apply_filters( 'feas_freeword_exclude_class', $ret_52_class, $args ) );

				/**
				 * Filter apply to the text "Exclude"
				 */
				$ret_52_text = '除外';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_52_text = esc_html( apply_filters( 'feas_freeword_exclude_text', $ret_52_text, $args ) );

				$checked = '';
				if ( isset( $_GET["{$ret_52_name}"] ) && '1' === $_GET["{$ret_52_name}"] ) {
					$checked = 'checked';
				}

				$exclude_html  = "<label for='{$ret_52_id}' class='{$ret_52_class}'>";
				$exclude_html .= "<input type='checkbox' id='{$ret_52_id}' name='{$ret_52_name}' value='1' {$checked} />";
				$exclude_html .= $ret_52_text;
				$exclude_html .= "</label>";
			}

			/**
			 *
			 * 完全一致オプション
			 *
			 */
			$exact_html = '';
			$exact_ui_flag = $get_data[$cols[53]];
			if ( '2' === $exact_ui_flag ) {

				// Sanitize
				$ret_53_id    = esc_attr( "feas_{$manag_no}_{$number}_exact" );
				$ret_53_name  = esc_attr( "feas_exact_{$number}" );

				/*
				 * Filter for class
				 */
				$ret_53_class = 'feas_freeword_exact';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_53_class = esc_attr( apply_filters( 'feas_freeword_exact_class', $ret_53_class, $args ) );

				/*
				 * Filter apply to the text "Exclude"
				 */
				$ret_53_text = '完全一致';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_53_text = esc_html( apply_filters( 'feas_freeword_exact_text', $ret_53_text, $args ) );

				$checked = '';
				if ( isset( $_GET["{$ret_53_name}"] ) && '1' === $_GET["{$ret_53_name}"] ) {
					$checked = 'checked';
				}

				$exact_html  = "<label for='{$ret_53_id}' class='{$ret_53_class}'>";
				$exact_html .= "<input type='checkbox' id='{$ret_53_id}' name='{$ret_53_name}' value='1' {$checked} />";
				$exact_html .= $ret_53_text;
				$exact_html .= "</label>";
			}

			if ( 'c' === $andor_ui_flag || '2' === $exclude_ui_flag || '2' === $exact_ui_flag ) {

				$tmp_html  = '<div class="feas_inline_group">';
				$tmp_html .= $html;
				$tmp_html .= '<div class="feas_wrap_options">';
				$tmp_html .= $andor_html . $exclude_html . $exact_html;
				$tmp_html .= '</div>';
				$tmp_html .= "</div>";

				$html = $tmp_html;
			}

			if ( '' !== $get_data[$cols[20]] ) {
				$html .= create_specifies_the_key_element( $get_data, $number );
			}

			/**
			 *
			 * inputタグ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'attr'     => $attr,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$html = apply_filters( 'feas_archive_freeword_group_html', $html, $args );

			$ret_ele .= $html;

			break;

		case 'f':

			break;

		/**
		 *	テキスト入力でアーカイブ検索
		 */
		case 'search_by_text':

			$s_keyword = '';
			if ( isset( $_GET['fe_form_no'] ) && $manag_no == $_GET['fe_form_no'] ) {
				if ( isset( $_GET['search_by_text_' . $number] ) ) {
					$s_keyword = $_GET['search_by_text_' . $number];
				}
			} else if ( $default_value ) {
				$s_keyword = $default_value[ 0 ];
			}

			/**
			 *
			 * inputのclassにかけるフィルター
			 *
			 */
			$class = 'feas_archive_text';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$class = apply_filters( 'feas_archive_text_class', $class, $args );

			/**
			 *
			 * inputのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$attr = apply_filters( 'feas_archive_text_attr', $attr, $args );

			// Sanitize
			$ret_id   = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_name = esc_attr( "search_by_text_{$number}" );
			$ret_val  = esc_attr( stripslashes( $s_keyword ) );

			// DatePicker
			if ( $search_with_dp ) {
				if ( '0' === $search_type ) {
					$input_type = 'number';
				} else if ( '1' === $search_type ) {
					$input_type = 'month';
				} else if ( '2' === $search_type ) {
					$input_type = 'date';
				}
			} else {
				$input_type = 'text';
			}

			$html = "<input type='{$input_type}' name='{$ret_name}' id='{$ret_id}' class='{$class}' value='{$ret_val}' {$attr} />";

			/**
			 *
			 * inputタグ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'attr'     => $attr,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$html = apply_filters( 'feas_archive_text_group_html', $html, $args );

			$ret_ele .= $html;

			break;

		/**
		 *	その他
		 */
		default:

			$s_keyword = '';
			if ( isset( $_GET['fe_form_no'] ) && $manag_no == $_GET['fe_form_no'] ) {
				if ( isset( $_GET['s_keyword_' . $number] ) ) {
					$s_keyword = $_GET['s_keyword_' . $number];
				}
			}

			/**
			 *
			 * inputのclassにかけるフィルター
			 *
			 */
			$class = 'feas_archive_default' . esc_attr( $depth );
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$class = apply_filters( 'feas_archive_default_class', $class, $args );

			/**
			 *
			 * inputのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$attr = apply_filters( 'feas_archive_default_attr', $attr, $args );

			// Sanitize
			$ret_id   = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_name = esc_attr( "s_keyword_{$number}" );
			$ret_val  = esc_attr( stripslashes( $s_keyword ) );

			$html  = "<input type='text' name='{$ret_name}' id='{$ret_id}' class='{$class}' value='{$ret_val}' {$attr} />";

			/**
			 *
			 * inputタグ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'attr'     => $attr,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$html = apply_filters( 'feas_archive_default_group_html', $html, $args );

			$ret_ele .= $html;

			break;
	}

	return $ret_ele;
}

/*============================
	タクソノミー（taxonomy）のエレメント作成
 ============================*/
function create_category_element( $get_data, $number ) {

	global $wpdb, $cols, $manag_no, $feadvns_search_target, $feadvns_show_count, $feadvns_include_sticky, $total_cnt, $wp_version, $cols_transient, $feadvns_exclude_id, $feadvns_default_cat, $feadvns_exclude_term_id, $feadvns_default_page;

	$nocnt = false;
	$exclude_post_ids = $default_page = '';
	$sql = $excat = $exids = $exid = $target_pt = $target_sp = $showcnt = $ret_ele = $order_by = $taxonomy = $lang = $lang_id = $polylang_sql = $wpml_sql = $child_html = null;
	$excat_array = $sticky = $q_term_id = $sp = $get_cats = array();

	// 検索対象のpost_typeを取得
	$target_pt_tmp = get_option( $feadvns_search_target . $manag_no );
	if ( $target_pt_tmp ) {
		$target_pt = "'" . implode( "','", (array) $target_pt_tmp ) . "'";
	} else {
		$target_pt = "'post'";
	}

	// 固定記事(Sticky Posts)を検索対象から省く場合、カウントに含めない
	$target_sp = get_option( $feadvns_include_sticky . $manag_no );
	if ( 'yes' != $target_sp ) {
		$sticky = get_option( 'sticky_posts' );
		if ( ! empty( $sticky ) ) {
			$sp = array_merge( $sp, $sticky ); // 除外IDにマージ
		}
	}

	// 投稿ステータス
	if ( in_array( 'attachment', (array) $target_pt_tmp ) ) {
		$post_status = "'publish', 'inherit'";
	} else {
		$post_status = "'publish'";
	}

	// 固定条件 > タクソノミ／ターム
	$fixed_term = get_option( $feadvns_default_cat . $manag_no );

	// 固定条件 > 親ページ
	$default_page = get_option( $feadvns_default_page . $manag_no );
	if ( $default_page ) {
		$default_page = implode( ',', (array) $default_page );
		$default_page = " AND p.post_parent IN (" . esc_sql( $default_page ) . ")";
	}

	// 検索条件に件数を表示
	$showcnt = get_option( $feadvns_show_count . $manag_no );

	// 除外する記事ID
	$exclude_id = get_option( $feadvns_exclude_id . $manag_no );
	if ( $exclude_id ) {
		$sp = array_merge( $sp, $exclude_id ); // 除外IDにマージ
	}

	// 検索結果から除外するタームID（全体）
	// タームごとのカウントに反映するため
	$exclude_term_id = get_option( $feadvns_exclude_term_id . $manag_no );
	if ( $exclude_term_id ) {
		$args['cat']      = $exclude_term_id;
		$args['format']   = 'array';
		$args['mode']     = 'exclude';
		$dcat['orderby']  = '';
		$exclude_post_ids = create_where_single_cat( $args );
	}

	// 除外タームのSQLを構成
	if ( $exclude_post_ids ) {
		$sp = array_merge( $sp, $exclude_post_ids ); // 除外IDにマージ
	}

	// 除外IDをカンマ区切りにする
	if ( $sp ) {
		$sp = implode( ',', $sp );
	}

	// 除外タームID（個別）が設定されている場合
	if ( isset( $get_data[$cols[11]] ) && $get_data[$cols[11]] != '' ) {
		$exids = explode( ',', $get_data[$cols[11]] );
	}

	// 0件のタームを表示しない設定の場合
	if ( isset( $get_data[$cols[14]] ) && $get_data[$cols[14]] == 'no' ) {
		$nocnt = true;
	}
	$nocnt_js = ( $nocnt == true ) ? 'no' : 'yes'; // JSへ渡すため真偽値を文字列に変換

	// タクソノミのトップ階層の場合
	if ( substr( $get_data[$cols[2]], 0, 4 ) == "par_" ) {

		// タクソノミ名を指定
		$taxonomy = substr( $get_data[$cols[2]], 4, strlen( $get_data[$cols[2]] ) - 4 );

		// parentとして0を代入
		$get_data[$cols[2]] = 0;
	}

	// 条件内の並び順
	$order_by = " t.term_id ASC ";

	if ( isset( $get_data[$cols[5]] ) ) {

		switch ( (string) $get_data[$cols[5]] ) {
			case '0':
			case '1':
			case 'c':
				$order_by = " t.term_id ";
				break;
			case '2':
			case '3':
			case 'd':
				$order_by = " t.name ";
				break;
			case '4':
			case '5':
			case 'e':
				$order_by = " t.slug ";
				break;
			case '6':
			case 'f':
				$order_by = " t.term_order ";
				break;
			case '7':
			case 'g':
				$order_by = " RAND() ";
				break;
			default:
				$order_by = " t.term_id ";
				break;
		}
	}

	// 条件内の並び順 昇順/降順
	$order = " ASC";

	if ( isset( $get_data[$cols[35]] ) ) {
		switch ( $get_data[$cols[35]] ) {

			case 'asc':
				$order = " ASC";
				break;
			case 'desc':
				$order = " DESC";
				break;
			default:
				$order = " ASC";
				break;
		}
	}

	// キャッシュ準備
	if ( 0 === $get_data[$cols[2]] ) {
		$parent_id = $taxonomy;
	} else {
		$parent_id = (int) $get_data[$cols[2]];
	}

	// 「要素内の並び順」が「自由記述」の場合は、ターム一覧をDBから呼び出す代わりに記述内容で配列get_catsを構成
	if ( 'b' === $get_data[$cols[5]] ) {

		$options = $get_data[$cols[36]];

		if ( ! empty( $options ) ) {

			// 行数分ループを回す
			for ( $i = 0; $cnt = count( $options ), $i < $cnt; $i++ ) {

				if ( empty( $options[$i] ) )
					continue;

				$get_cats[$i] = new stdClass();

				// 値
				$get_cats[$i]->term_id = $options[$i]['value'];

				// 表記
				$get_cats[$i]->name = $options[$i]['text'];

				// 階層
				$get_cats[$i]->depth = $options[$i]['depth'];
			}
		}

	} else { // 「自由記述」以外

		// キャッシュ
		if ( false === ( $get_cats = feas_cache_judgment( $manag_no, 'taxonomy', $parent_id ) ) ) {

			// Polylang
			if ( in_array( 'polylang/polylang.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

				$polylang = get_option( 'polylang' );
				if ( $polylang ) {
					$lang = $polylang['default_lang'];
				}

				$langVar = get_query_var( 'lang' );
				if ( $langVar ) {
					$lang = $langVar;
				}

				if ( $lang ) {
					$sql = <<<SQL
SELECT tt.term_id
FROM {$wpdb->term_taxonomy} AS tt
LEFT JOIN {$wpdb->terms} AS t
ON tt.term_id = t.term_id
WHERE t.slug = %s
LIMIT 1
SQL;
					$sql = $wpdb->prepare( $sql, $lang );
					$lang_id = $wpdb->get_var( $sql );
					if ( $lang_id ) {
						$addSql = <<<SQL
AND tr.object_id IN (
SELECT tr.object_id
FROM {$wpdb->terms} AS t
LEFT JOIN {$wpdb->term_relationships} AS tr ON tr.term_taxonomy_id = t.term_id
LEFT JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
WHERE tr.term_taxonomy_id = %d
)
SQL;
						$polylang_sql = $wpdb->prepare( $addSql, $lang_id );
					}
				}
			}

			// WPML
			if ( in_array( 'sitepress-multilingual-cms/sitepress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				$wpml_lang = apply_filters( 'wpml_current_language', NULL );
				if ( $wpml_lang ) {
					$args = array( 'public' => true );
					$allTaxs = get_taxonomies( $args, 'objects' );
					$wpmlTaxList = [];
					if ( $allTaxs ) {
						foreach ( $allTaxs as $tax ) {
							if ( 'post_format' !== $tax->name ) {
								foreach( $target_pt_tmp as $pt ) {
									if ( in_array( $pt, $tax->object_type ) ) {
										$wpmlTaxList[] = "tax_{$tax->name}";
									}
								}
							}
						}
					}
					$commaSepPlaceholder = implode( ', ', array_fill( 0, count( $wpmlTaxList ), '%s' ) );
					$sql  = " AND wpml_translations.element_type IN( {$commaSepPlaceholder} )";
					$sql  = $wpdb->prepare( $sql, $wpmlTaxList );
					$sql .= " AND wpml_translations.language_code = %s ";
					$wpml_sql = $wpdb->prepare( $sql, $wpml_lang );
				}
			}

			// Integrate SQLs
			$sql = <<<SQL
SELECT t.term_id, t.name
FROM {$wpdb->terms} AS t
LEFT JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
LEFT JOIN {$wpdb->term_relationships} AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
SQL;
			if ( $wpml_sql ) {
				$sql .= " LEFT JOIN {$wpdb->prefix}icl_translations AS wpml_translations ON t.term_id = wpml_translations.element_id";
			}
			$addSql = ' WHERE tt.parent = %d ';
			$sql .= $wpdb->prepare( $addSql, $get_data[$cols[2]] );
			if ( $taxonomy ) {
				$addSql = ' AND tt.taxonomy = %s ';
				$sql .= $wpdb->prepare( $addSql, $taxonomy );
			}
			if ( $exids ) {
				$exids_placeholders = implode( ', ', array_fill( 0, count( $exids ), '%d' ) );
				$addSql = " AND t.term_id NOT IN ( {$exids_placeholders} ) ";
				$sql .= $wpdb->prepare( $addSql, $exids );
			}
			if ( $polylang_sql ) {
				$sql .= $polylang_sql;
			}
			if ( $wpml_sql ) {
				$sql .= $wpml_sql;
			}
			$sql .= " GROUP BY t.term_id";
			$sql .= " ORDER BY {$order_by} {$order}";

			$get_cats = $wpdb->get_results( $sql );

			feas_cache_create( $manag_no, 'taxonomy', $parent_id, $get_cats );
		}
	}

	$cnt_ele = count( $get_cats );

	// 件数を取得してキャッシュ保存
	if ( $get_cats ) {

		$term_cnt = array();
		foreach( $get_cats as $term_id ) {
			if ( false === ( $cnt = feas_cache_judgment( $manag_no, 'term_cnt_' . $term_id->term_id, false ) ) ) {
				$sql  = " SELECT count( p.ID ) AS cnt FROM {$wpdb->posts} AS p";
				$sql .= " INNER JOIN {$wpdb->term_relationships} AS tr ON p.ID = tr.object_id";
				if ( $polylang_sql ) {
					$sql .= " INNER JOIN {$wpdb->term_relationships} AS tr2 ON p.ID = tr2.object_id";
				}
				if ( $fixed_term ) {
					$sql .= " INNER JOIN {$wpdb->term_relationships} AS tr3 ON p.ID = tr3.object_id";
				}
				if ( $wpml_sql ) {
					$sql .= " INNER JOIN {$wpdb->prefix}icl_translations AS wpml_translations ON p.ID = wpml_translations.element_id";
				}
				$sql .= " WHERE 1=1";
				if ( $sp ) {
					$sql .= " AND p.ID NOT IN ( $sp )";
				}
				if ( $default_page ) {
					$sql .= $default_page;
				}
				$sql .= " AND tr.term_taxonomy_id = " . esc_sql( $term_id->term_id );
				if ( $fixed_term ) {
					$sql .= " AND tr3.term_taxonomy_id = " . esc_sql( $fixed_term );
				}
				if ( $lang_id ) {
					$addSql = " AND tr2.term_taxonomy_id IN ( %d )";
					$sql .= $wpdb->prepare( $addSql, $lang_id );
				}
				if ( $wpml_sql ) {
					$add_sql = " AND wpml_translations.language_code = %s ";
					$sql .= $wpdb->prepare( $add_sql, $wpml_lang );
				}
				$sql .= " AND p.post_type IN( $target_pt )";
				$sql .= " AND p.post_status IN ( {$post_status} )";
				$cnt = $wpdb->get_row( $sql );
				feas_cache_create( $manag_no, 'term_cnt_' . $term_id->term_id, false, $cnt );
			}
			$term_cnt[] = $cnt;
		}
	}

	// 表示する階層の深さの指定が未入力の場合、-1 (=無制限)を代入
	if ( $get_data[$cols[10]] == "" || $get_data[$cols[10]] == null || !is_numeric( $get_data[$cols[10]] ) ) {
		$term_depth = -1;
	} else {
		$term_depth = intval( $get_data[$cols[10]] );
	}

	// 階層が0(=現在の階層のみ表示)以外の場合、子カテゴリ表示のためにGET値を代入してcreate_child_op等に渡す
	if ( 0 !== $term_depth ) {
		if ( isset( $_GET['search_element_' . $number] ) ) {
			if ( is_array( $_GET['search_element_' . $number] ) ) {
				$q_term_id = $_GET['search_element_' . $number];
			} else {
				$q_term_id[] = esc_html( $_GET['search_element_' . $number] );
			}
		}
	}

	// 未選択時の文字列
	$noselect_text = $get_data[$cols[27]];
	$noselect_text_array = explode(',', $noselect_text );
	$labelCnt = count( $noselect_text_array );

	// デフォルト値
	$default_value = $get_data[$cols[39]];
	if ( '' !== $default_value ) {
		$default_value = explode( ',', $default_value );
	}

	// 形式
	switch ( $get_data[$cols[4]] ) {

		// ドロップダウン
		case 1:
		case 'a':

			// Ajaxフィルタリング
			if ( 'no' == $get_data[$cols[19]] ) {

				$checked_before = '';
				$ret_opt = '';

				for ( $i_ele = 0; $i_ele < $cnt_ele; $i_ele++ ) {

					// 0件タームは表示しない場合（post_status処理後の件数を再評価）
					if ( $nocnt && $term_cnt[$i_ele]->cnt === "0")
						continue;

					$selected = '';
					if ( isset( $_GET["fe_form_no"] ) && $_GET["fe_form_no"] == $manag_no ) {
						if ( isset( $_GET['search_element_' . $number] ) ) {
							if ( $_GET['search_element_' . $number][0] == $get_cats[$i_ele]->term_id ) {
								$selected = ' selected';
								$checked_before = $get_cats[$i_ele]->term_id;
							}
						}
					} elseif ( $default_value ) {
						if ( is_array( $default_value ) ) {
							for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
								if ( $default_value[$i_lists] == $get_cats[$i_ele]->term_id ) {
									$selected = ' selected';
								}
							}
						}
					}

					// カテゴリ毎の件数を表示する設定の場合、件数を代入
					$cat_cnt = '';
					if ( 'yes' == $showcnt ) {
						$cat_cnt = " (" . $term_cnt[$i_ele]->cnt . ") ";
					}

					$depth = '01';
					$indentSpace = '';

					// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassとインデントを準備
					if ( 'b' === $get_data[$cols[5]] ) {
						if ( '1' !== $get_cats[$i_ele]->depth ) {
							$depth = str_pad( $get_cats[$i_ele]->depth, 2, '0', STR_PAD_LEFT );
							for ( $i_depth = 1; $i_depth < $get_cats[$i_ele]->depth; $i_depth++ ) {
								$indentSpace .= '&nbsp;&nbsp;';
							}
						}
					}

					/**
					 *
					 * optionのclassにかけるフィルター
					 *
					 */
					$class = 'feas_clevel_' . esc_attr( $depth );
					$args = array(
						'manag_no'      => (int) $manag_no,
						'number'        => (int) $number,
						'cnt'           => (int) $i_ele,
						'text'          => esc_attr( $get_cats[$i_ele]->name ),
						'value'         => esc_attr( $get_cats[$i_ele]->term_id ),
						'selected'      => $selected,
						'show_post_cnt' => $showcnt,
						'post_cnt'      => $term_cnt[$i_ele]->cnt,
					);
					$class = apply_filters( 'feas_term_dropdown_class', $class, $args );

					// optionのattrにかけるフィルター
					$attr = '';
					$args = array(
						'manag_no'      => (int) $manag_no,
						'number'        => (int) $number,
						'cnt'           => (int) $i_ele,
						'class'         => $class,
						'text'          => esc_attr( $get_cats[$i_ele]->name ),
						'value'         => esc_attr( $get_cats[$i_ele]->term_id ),
						'selected'      => $selected,
						'show_post_cnt' => $showcnt,
						'post_cnt'      => $term_cnt[$i_ele]->cnt,
					);
					$attr = apply_filters( 'feas_term_dropdown_attr', $attr, $args );

					// Sanitaize
					$ret_id   = esc_attr( "feas_{$manag_no}_{$number}_0_{$i_ele}" );
					$ret_val  = esc_attr( $get_cats[$i_ele]->term_id );
					$ret_text = esc_html( $get_cats[$i_ele]->name . $cat_cnt );

					$html  = "<option id='{$ret_id}' value='{$ret_val}' class='{$class}' {$attr} {$selected}>";
					$html .= $indentSpace . $ret_text;
					$html .= "</option>\n";

					// optionごとにかけるフィルター
					$args = array(
						'manag_no'      => (int) $manag_no,
						'number'        => (int) $number,
						'cnt'           => (int) $i_ele,
						'class'         => $class,
						'attr'          => $attr,
						'text'          => esc_attr( $get_cats[$i_ele]->name ),
						'value'         => esc_attr( $get_cats[$i_ele]->term_id ),
						'depth'         => 1,
						'show_post_cnt' => $showcnt,
						'post_cnt'      => $term_cnt[$i_ele]->cnt,
						'ajax'          => 1,
					);
					$html = apply_filters( 'feas_term_dropdown_html', $html, $args );

					$ret_opt .= $html;

				}

				/**
				 *
				 * selectのclassにかけるフィルター
				 *
				 */
				$class = "feas_term_dropdown ajax_{$number}_0";
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'parent'        => 0,
					'depth'         => 0,
					'show_post_cnt' => $showcnt,
					'ajax'          => 1,
				);
				$class = apply_filters( 'feas_term_dropdown_group_class', $class, $args );

				// selectのattrにかけるフィルター
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'parent'        => 0,
					'depth'         => 0,
					'class'         => $class,
					'show_post_cnt' => $showcnt,
					'ajax'          => 1,
				);
				$attr = apply_filters( 'feas_term_dropdown_group_attr', $attr, $args );

				// Sanitize
				$ret_name     = esc_attr( "search_element_{$number}[]" );
				$ret_id       = esc_attr( "feas_{$manag_no}_{$number}" );
				$ret_onchange = esc_attr( "ajax_filtering_next({$manag_no},{$number},0,'{$showcnt}',{$term_depth},'{$nocnt_js}')" );
				$ret_txt      = esc_html( $noselect_text_array[0] );

				$ret_ele .= "<select name='{$ret_name}' class='{$class}' onChange='{$ret_onchange}' {$attr}>\n";
				$ret_ele .= "<option id='{$ret_id}_none' value=''>";
				$ret_ele .= $ret_txt;
				$ret_ele .= "</option>\n";
				$ret_ele .= $ret_opt;
				$ret_ele .= "</select>\n";

				// select全体にかけるフィルター
				$args = array(
					'manag_no'            => (int) $manag_no,
					'number'              => (int) $number,
					'parent'              => 0,
					'depth'               => 0,
					'show_post_cnt'       => $showcnt,
					'ajax'                => 1,
					'ret_opt'             => $ret_opt,
					'term_depth'          => $term_depth,
					'nocnt_js'            => $nocnt_js,
					'noselect_text_array' => $ret_txt,
					'class'               => $class,
					'attr'                => $attr,
				);
				$ret_ele = apply_filters( 'feas_term_dropdown_group_html', $ret_ele, $args );

				/*
				 *
				 * 階層の指定がある場合 or 検索実行後
				 *
				 */
				if ( 1 < $term_depth || isset( $_GET['fe_form_no'] ) ) {

					$childDepth = $searchQuery = '';

					/*
					 * 検索実行後
					 * search.php遷移時に子カテゴリのドロップダウンを生成
					 */
					if ( isset( $_GET['search_element_' . $number] ) && is_array( $_GET['search_element_' . $number] ) ) {

						if ( -1 === $term_depth ) { // 階層指定がない場合
							$childDepth = count( $_GET['search_element_' . $number] );
						} else {
							$childDepth = $term_depth;
						}

						$searchQuery = $_GET['search_element_' . $number];

					} else { // デフォルト値の設定がある場合（検索前）

						$childDepth = $term_depth;

						if ( ! empty( $default_value ) ) {
							$searchQuery = $default_value; // 配列
						}
					}

					if ( $childDepth ) {

						// ドロップダウン2つ目以降なので0ではなく1からカウンターを回す
						for ( $outer = 1; $outer < $childDepth; $outer++ ) {

							$get_cats = '';

							// 検索実行後 or デフォルト値がある場合、子ターム一覧取得
							if ( isset( $searchQuery[$outer-1] ) && ! empty( $searchQuery[$outer-1] ) ) {

								// キャッシュ優先
								if ( false === ( $get_cats = feas_cache_judgment( $manag_no, 'taxonomy', (int) $searchQuery[$outer-1] ) ) ) {
									// ターム一覧を取得
									$sql  = " SELECT t.term_id, t.name FROM {$wpdb->terms} AS t";
									$sql .= " LEFT JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id";
									$sql .= " LEFT JOIN {$wpdb->term_relationships} AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id";
									//$sql .= " LEFT JOIN {$wpdb->posts} AS p ON tr.object_id = p.ID";
									$sql .= " WHERE tt.parent = " . esc_sql( $searchQuery[$outer-1] );
									if ( $taxonomy ) $sql .= " AND tt.taxonomy = '" . esc_sql( $taxonomy ) . "'";
									if ( $exids )    $sql .= " AND t.term_id NOT IN (" . esc_sql( $exids ) . ")";
									//$sql .= " AND p.post_type IN( {$target_pt} )";
									$sql .= " GROUP BY t.term_id";
									$sql .= " ORDER BY " . esc_sql( $order_by );
									$get_cats = $wpdb->get_results( $sql );

									feas_cache_create( $manag_no, 'taxonomy', (int) $searchQuery[$outer-1], $get_cats );
								}

								$cnt_ele = count( $get_cats );

								if ( $get_cats ) {

									$term_cnt = array();

									foreach( $get_cats as $term_id ) {

										// キャッシュ優先
										if ( false === ( $cnt = feas_cache_judgment( $manag_no, 'term_cnt_' . $term_id->term_id, false ) ) ) {
											// 件数を取得
											$sql  = " SELECT count( p.ID ) AS cnt FROM {$wpdb->posts} AS p";
											$sql .= " INNER JOIN {$wpdb->term_relationships} AS tr ON p.ID = tr.object_id";
											if ( $fixed_term ) $sql .= " INNER JOIN {$wpdb->term_relationships} AS tr2 ON p.ID = tr2.object_id";
											$sql .= " WHERE 1=1";
											if ( $sp ) $sql .= " AND p.ID NOT IN ( $sp )";
											$sql .= " AND tr.term_taxonomy_id = " . esc_sql( $term_id->term_id );
											if ( $fixed_term ) $sql .= " AND tr2.term_taxonomy_id = " . esc_sql( $fixed_term );
											$sql .= " AND p.post_type IN( $target_pt )";
											$sql .= " AND p.post_status = 'publish'";

											$cnt = $wpdb->get_row( $sql );
											feas_cache_create( $manag_no, 'term_cnt_' . $term_id->term_id, false, $cnt );
										}
										$term_cnt[] = $cnt;
									}
								}
							}

							$ret_opt = '';

							if ( $get_cats ) {

								for ( $inner = 0; $inner < $cnt_ele; $inner++ ) {

									// 0件タームは表示しない場合
									if ( $nocnt && $term_cnt[$inner]->cnt === "0" ) {
										continue;
									}

									$selected = '';
									if ( isset( $searchQuery[$outer] ) ) {
										if ( $searchQuery[$outer] == $get_cats[$inner]->term_id ) {
											$selected = ' selected';
											$checked_before = $get_cats[$inner]->term_id;
										}
									}

									$cat_cnt = '';
									if ( 'yes' === $showcnt ) {
										$cat_cnt = " (" . $term_cnt[$inner]->cnt . ") ";
									}

									/**
									 *
									 * optionのclassにかけるフィルター
									 *
									 */
									$class = "feas_clevel_01";
									$args = array(
										'manag_no'      => (int) $manag_no,
										'number'        => (int) $number,
										'parent'        => 0,
										'depth'         => 0,
										'text'          => esc_attr( $get_cats[$inner]->name ),
										'value'         => esc_attr( $get_cats[$inner]->term_id ),
										'selected'      => $selected,
										'show_post_cnt' => $showcnt,
										'post_cnt'      => $term_cnt[$inner]->cnt,
										'ajax'          => 1,
									);
									$class = apply_filters( 'feas_term_dropdown_class', $class, $args );

									// 各optionのattrにかけるフィルター
									$attr = '';
									$args = array(
										'manag_no'      => (int) $manag_no,
										'number'        => (int) $number,
										'cnt'           => (int) $inner,
										'class'         => $class,
										'depth'         => (int) $outer,
										'text'          => esc_attr( $get_cats[$inner]->name ),
										'value'         => esc_attr( $get_cats[$inner]->term_id ),
										'selected'      => $selected,
										'show_post_cnt' => $showcnt,
										'post_cnt'      => $term_cnt[$inner]->cnt,
										'ajax'          => 1,
									);
									$attr = apply_filters( 'feas_term_dropdown_attr', $attr, $args );

									// Sanitaize
									$ret_id   = esc_attr( "feas_{$manag_no}_{$number}_{$outer}_{$inner}" );
									$ret_val  = esc_attr( $get_cats[$inner]->term_id );
									$ret_text = esc_html( $get_cats[$inner]->name . $cat_cnt );

									$html  = "<option id='{$ret_id}' value='{$ret_val}' class='{$class}' {$attr} {$selected}>";
									$html .= $ret_text;
									$html .= "</option>\n";

									// 各optionごとにかけるフィルター
									$args = array(
										'manag_no'      => (int) $manag_no,
										'number'        => (int) $number,
										'cnt'           => (int) $inner,
										'class'         => $class,
										'attr'          => $attr,
										'text'          => esc_attr( $get_cats[$inner]->name ),
										'value'         => esc_attr( $get_cats[$inner]->term_id ),
										'depth'         => (int) $outer,
										'show_post_cnt' => $showcnt,
										'post_cnt'      => $term_cnt[$inner]->cnt,
										'ajax'          => 1,
									);
									$html = apply_filters( 'feas_term_dropdown_html', $html, $args );

									$ret_opt .= $html;
								}
							}

							/**
							 *
							 * selectのclassにかけるフィルター
							 *
							 */
							$class = 'feas_term_dropdown ' . esc_attr( "ajax_{$number}_{$outer}" );
							$args = array(
								'manag_no'      => (int) $manag_no,
								'number'        => (int) $number,
								'parent'        => 0,
								'depth'         => (int) $outer,
								'show_post_cnt' => $showcnt,
								'ajax'          => 1,
							);
							$class = apply_filters( 'feas_term_dropdown_group_class', $class, $args );

							// selectのattrにかけるフィルター
							$attr = '';
							$args = array(
								'manag_no'      => (int) $manag_no,
								'number'        => (int) $number,
								'parent'        => 0,
								'depth'         => (int) $outer,
								'class'         => $class,
								'show_post_cnt' => $showcnt,
								'ajax'          => 1,
							);
							$attr = apply_filters( 'feas_term_dropdown_group_attr', $attr, $args );

							// Sanitize
							$ret_name     = esc_attr( "search_element_{$number}[]" );
							$ret_id       = esc_attr( "feas_{$manag_no}_{$number}" );
							$ret_onchange = esc_attr( "ajax_filtering_next({$manag_no},{$number},{$outer},'{$showcnt}',{$term_depth},'{$nocnt_js}')" );
							$ret_txt      = esc_html( ( isset( $noselect_text_array[$outer] ) ? $noselect_text_array[$outer] : $noselect_text_array[$labelCnt-1] ) );

							$child_ele  = "<select name='{$ret_name}' class='{$class}' onChange='{$ret_onchange}' {$attr}>\n";
							$child_ele .= "<option id='{$ret_id}_none' value=''>";
							$child_ele .= $ret_txt;
							$child_ele .= "</option>\n";
							$child_ele .= $ret_opt;
							$child_ele .= "</select>\n";

							// select全体にかけるフィルター
							$args = array(
								'manag_no'            => (int) $manag_no,
								'number'              => (int) $number,
								'parent'              => 0,
								'depth'               => (int) $outer,
								'show_post_cnt'       => $showcnt,
								'ajax'                => 1,
								'ret_opt'             => $ret_opt,
								'term_depth'          => $term_depth,
								'nocnt_js'            => $nocnt_js,
								'noselect_text_array' => $ret_txt,
								'class'               => $class,
								'attr'                => $attr,
							);
							$ret_ele .= apply_filters( 'feas_term_dropdown_group_html', $child_ele, $args );
						}
					}

				}

				// Ajaxフィルタリング全体にかけるフィルター
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
					'parent'   => 0,
					'depth'    => -1,
					'show_post_cnt' => $showcnt,
					'ajax'     => 1,
				);
				$ret_ele = apply_filters( 'feas_term_ajax_dropdown_group_html', $ret_ele, $args );


			} else { // 通常のドロップダウン

				$ret_opt = '';

				for ( $i_ele = 0; $i_ele < $cnt_ele; $i_ele++ ) {

					// 0件タームは表示しない場合
					if ( $nocnt && $term_cnt[$i_ele]->cnt === "0" )
						continue;

					$selected = '';
					if ( isset( $_GET["fe_form_no"] ) && $_GET["fe_form_no"] == $manag_no ) {
						if ( isset( $_GET['search_element_' . $number] ) ) {
							if ( $_GET['search_element_' . $number] == $get_cats[$i_ele]->term_id ) {
								$selected = ' selected';
							}
						}
					} elseif ( $default_value ) {
						if ( is_array( $default_value ) ) {
							for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
								if ( $default_value[$i_lists] == $get_cats[$i_ele]->term_id ) {
									$selected = ' selected';
								}
							}
						}
					}

					// カテゴリ毎の件数を表示する設定の場合、件数を代入
					$cat_cnt = '';
					if ( "yes" == $showcnt ) {
						$cat_cnt = " (" . $term_cnt[$i_ele]->cnt . ") ";
					}

					$depth = '01';
					$indentSpace = '';

					// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassとインデントを準備
					if ( 'b' === $get_data[$cols[5]] ) {
						if ( '1' !== $get_cats[$i_ele]->depth ) {
							$depth = str_pad( $get_cats[$i_ele]->depth, 2, '0', STR_PAD_LEFT );
							for ( $i_depth = 1; $i_depth < $get_cats[$i_ele]->depth; $i_depth++ ) {
								$indentSpace .= '&nbsp;&nbsp;';
							}
						}
					}

					/**
					 *
					 * optionのclassにかけるフィルター
					 *
					 */
					$class = 'feas_clevel_' . esc_attr( $depth );
					$args = array(
						'manag_no'      => (int) $manag_no,
						'number'        => (int) $number,
						'cnt'           => (int) $i_ele,
						'parent'        => 0,
						'depth'         => 1,
						'text'          => esc_attr( $get_cats[$i_ele]->name ),
						'value'         => esc_attr( $get_cats[$i_ele]->term_id ),
						'selected'      => $selected,
						'show_post_cnt' => $showcnt,
						'post_cnt'      => $term_cnt[$i_ele]->cnt,
						'ajax'          => 0,
					);
					$class = apply_filters( 'feas_term_dropdown_class', $class, $args );

					/**
					 *
					 * optionのattrにかけるフィルター
					 *
					 */
					$attr = '';
					$args = array(
						'manag_no'      => (int) $manag_no,
						'number'        => (int) $number,
						'cnt'           => (int) $i_ele,
						'parent'        => 0,
						'depth'         => 1,
						'class'         => $class,
						'text'          => esc_attr( $get_cats[$i_ele]->name ),
						'value'         => esc_attr( $get_cats[$i_ele]->term_id ),
						'selected'      => $selected,
						'show_post_cnt' => $showcnt,
						'post_cnt'      => $term_cnt[$i_ele]->cnt,
						'ajax'          => 0,
					);
					$attr = apply_filters( 'feas_term_dropdown_attr', $attr, $args );

					// Sanitaize
					$ret_id  = esc_attr( "feas_{$manag_no}_{$number}_{$i_ele}" );
					$ret_val = esc_attr( $get_cats[$i_ele]->term_id );
					$ret_txt = esc_html( $get_cats[$i_ele]->name . $cat_cnt );

					$html  = "<option id='{$ret_id}' value='{$ret_val}' class='{$class}' {$attr} {$selected}>";
					$html .= $indentSpace . $ret_txt;
					$html .= "</option>\n";

					// 「自由記述」ではない、かつ階層が０(=現在の階層のみ表示)以外の場合、子カテゴリを表示
					if ( 'b' !== $get_data[$cols[5]] && 0 !== $term_depth ) {

						// 子カテゴリ取得
						$args = [
							'par_id'      => $get_cats[$i_ele]->term_id,
							'term_depth'  => $term_depth,
							'class_cnt'   => 2,
							'q_term_id'   => $q_term_id,
							'nocnt'       => $nocnt,
							'exids'       => $exids,
							'sticky'      => $sticky,
							'showcnt'     => $showcnt,
							'manage_line' => null,
							'taxonomy'    => $taxonomy,
							'par_no'      => $i_ele,
							'number'      => $number,
							'sp'          => $sp,
							'to'          => $order_by,
							'default_page' => $default_page,
						];
						$child_html = create_child_op( $args );
						$html .= $child_html;
					}

					// 各optionごとにかけるフィルター
					$args = array(
						'manag_no'      => (int) $manag_no,
						'number'        => (int) $number,
						'cnt'           => (int) $i_ele,
						'parent'        => 0,
						'depth'         => 1,
						'text'          => esc_attr( $get_cats[$i_ele]->name ),
						'value'         => esc_attr( $get_cats[$i_ele]->term_id ),
						'show_post_cnt' => $showcnt,
						'post_cnt'      => (int) $term_cnt[$i_ele]->cnt,
						'ajax'          => 0,
						'child_obj'     => $child_html,
					);
					$html = apply_filters( 'feas_term_dropdown_html', $html, $args );

					$ret_opt .= $html;
				}

				/**
				 *
				 * selectのclassにかけるフィルター
				 *
				 */
				$class = 'feas_term_dropdown';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'parent'        => 0,
					'depth'         => 1,
					'show_post_cnt' => $showcnt,
					'ajax'          => 0,
				);
				$class = apply_filters( 'feas_term_dropdown_group_class', $class, $args );

				// selectのattrにかけるフィルター
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'parent'        => 0,
					'depth'         => 1,
					'class'         => $class,
					'show_post_cnt' => $showcnt,
					'ajax'          => 0,
				);
				$attr = apply_filters( 'feas_term_dropdown_group_attr', $attr, $args );

				// Sanitize
				$ret_name = esc_attr( "search_element_{$number}" );
				$ret_id   = esc_attr( "feas_{$manag_no}_{$number}" );
				$ret_txt  = esc_html( $noselect_text_array[0] );

				$ret_ele .= "<select name='{$ret_name}' id='{$ret_id}' class='{$class}' {$attr}>\n";
				$ret_ele .= "<option id='{$ret_id}_none' value=''>";
				$ret_ele .= $ret_txt;
				$ret_ele .= "</option>\n";
				$ret_ele .= $ret_opt;
				$ret_ele .= "</select>\n";

				// select全体にかけるフィルター
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'parent'        => 0,
					'depth'         => 1,
					'show_post_cnt' => $showcnt,
					'ret_opt'       => $ret_opt,
					'ajax'          => 0,
				);
				$ret_ele = apply_filters( 'feas_term_dropdown_group_html', $ret_ele, $args );
			}

			break;

		// セレクトボックス
		case 2:
		case 'b':

			$ret_opt = '';
			$selected_cnt = 0;

			for ( $i_ele = 0, $cnt_ele = count( $get_cats ); $i_ele < $cnt_ele; $i_ele++ ) {

				// 0件タームは表示しない場合
				if ( $nocnt && $term_cnt[$i_ele]->cnt === "0" )
					continue;

				$selected = '';

				if ( isset( $_GET["fe_form_no"] ) && $_GET["fe_form_no"] == $manag_no ) {
					if ( isset( $_GET["search_element_" . $number] ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $_GET["search_element_" . $number] ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( isset( $_GET["search_element_" . $number][$i_lists] ) ) {
								if ( $_GET["search_element_" . $number][$i_lists] == $get_cats[$i_ele]->term_id ) {
									$selected = ' selected';
									$selected_cnt++;
								}
							}
						}
					}
				} elseif ( $default_value ) {
					if ( is_array( $default_value ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( $default_value[$i_lists] == $get_cats[$i_ele]->term_id ) {
								$selected = ' selected';
							}
						}
					}
				}

				// カテゴリ毎の件数を表示する設定の場合、件数を代入
				$cat_cnt = '';
				if ( 'yes' == $showcnt ) {
					$cat_cnt = " (" . $term_cnt[$i_ele]->cnt . ") ";
				}

				$depth = '01';
				$indentSpace = '';

				// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassとインデントを準備
				if ( 'b' === $get_data[$cols[5]] ) {
					if ( '1' !== $get_cats[$i_ele]->depth ) {
						$depth = str_pad( $get_cats[$i_ele]->depth, 2, '0', STR_PAD_LEFT );
						$indentSpace = '';
						for ( $i_depth = 1; $i_depth < $get_cats[$i_ele]->depth; $i_depth++ ) {
							$indentSpace .= '&nbsp;&nbsp;';
						}
					}
				}

				/**
				 *
				 * optionのclassにかけるフィルター
				 *
				 */
				$class = 'feas_clevel_' . esc_attr( $depth );
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'depth'         => 1,
					'text'          => esc_attr( $get_cats[$i_ele]->name ),
					'value'         => esc_attr( $get_cats[$i_ele]->term_id ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $term_cnt[$i_ele]->cnt,
				);
				$class = apply_filters( 'feas_term_multiple_class', $class, $args );

				/**
				 *
				 * 各optionのattrにかけるフィルター
				 *
				 */
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'depth'         => 1,
					'class'         => $class,
					'text'          => esc_attr( $get_cats[$i_ele]->name ),
					'value'         => esc_attr( $get_cats[$i_ele]->term_id ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $term_cnt[$i_ele]->cnt,
				);
				$attr = apply_filters( 'feas_term_multiple_attr', $attr, $args );

				// Sanitize
				$ret_id  = esc_attr( "feas_{$manag_no}_{$number}_{$i_ele}" );
				$ret_val = esc_attr( $get_cats[$i_ele]->term_id );
				$ret_txt = esc_html( $get_cats[$i_ele]->name . $cat_cnt );

				$html  = "<option id='{$ret_id}' value='{$ret_val}' class='{$class}' {$attr} {$selected}>";
				$html .= $indentSpace . $ret_txt;
				$html .= "</option>\n";

				// 階層が０(=現在の階層のみ表示)以外の場合、子カテゴリを表示
				if ( 'b' !== $get_data[$cols[5]] && 0 !== $term_depth ) {

					// 子カテゴリ取得
					$args = [
						'par_id'      => $get_cats[$i_ele]->term_id,
						'term_depth'  => $term_depth,
						'class_cnt'   => 2,
						'q_term_id'   => $q_term_id,
						'nocnt'       => $nocnt,
						'exids'       => $exids,
						'sticky'      => $sticky,
						'showcnt'     => $showcnt,
						'manage_line' => null,
						'taxonomy'    => $taxonomy,
						'par_no'      => $i_ele,
						'number'      => $number,
						'sp'          => $sp,
						'to'          => $order_by,
						'default_page' => $default_page,
					];
					$child_html = create_child_op( $args );
					$html .= $child_html;
				}

				/**
				 *
				 * 各オプションごとにかけるフィルター
				 *
				 */
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'depth'         => 1,
					'text'          => esc_attr( $get_cats[$i_ele]->name ),
					'value'         => esc_attr( $get_cats[$i_ele]->term_id ),
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $term_cnt[$i_ele]->cnt,
					'child_obj'     => $child_html,
				);
				$html = apply_filters( 'feas_term_multiple_html', $html, $args );

				// ループ前段に結合
				$ret_opt .= $html;
			}

			// iOSではセレクトボックスが1行にまとめられてしまい、selectedが1件も付いていないと「0項目」と表示されてしまい、未選択時テキストが表示されないため。
			$selected = '';
			if ( 0 === $selected_cnt ) {
				if ( wp_is_mobile() ) {
					$selected = ' selected';
				}
			}

			/**
			 *
			 * セレクトボックスのclassにかけるフィルター
			 *
			 */
			$class = 'feas_term_multiple';
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'show_post_cnt' => $showcnt,
			);
			$class = apply_filters( 'feas_term_multiple_group_class', $class, $args );

			/**
			 *
			 * セレクトボックスのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'class'         => $class,
				'show_post_cnt' => $showcnt,
			);
			$attr = apply_filters( 'feas_term_multiple_group_attr', $attr, $args );

			// Sanitize
			$ret_name  = esc_attr( "search_element_{$number}[]" );
			$ret_id    = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_txt   = esc_html( $noselect_text );

			$html  = "<select name='{$ret_name}' id='{$ret_id}' class='{$class}' size='5' multiple='multiple' {$attr}>\n";
			$html .= "<option id='{$ret_id}_none' value='' {$selected}>";
			$html .= $ret_txt;
			$html .= "</option>\n";
			$html .= $ret_opt;
			$html .= "</select>\n";

			/**
			 *
			 * セレクトボックス全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'parent'        => 0,
				'depth'         => 1,
				'show_post_cnt' => $showcnt,
				'ret_opt'       => $ret_opt,
			);
			$html = apply_filters( 'feas_term_multiple_group_html', $html, $args );

			// ループ前段に結合
			$ret_ele .= $html;

			break;

		/**
		 *	チェックボックス
		 */
		case 3:
		case 'c':

			$total_cnt = 0;	// 子カテゴリのチェックボックスと累積生成カウント数を取得のため

			for ( $i_ele = 0, $cnt_ele = count( $get_cats ); $i_ele < $cnt_ele; $i_ele++ ) {

				// 0件タームは表示しない場合
				if ( $nocnt && $term_cnt[$i_ele]->cnt === "0" )
					continue;

				$checked = '';
				if ( isset( $_GET["fe_form_no"] ) && $_GET["fe_form_no"] == $manag_no ) {
					if ( isset( $_GET["search_element_" . $number] ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $_GET["search_element_" . $number] ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( isset( $_GET["search_element_" . $number][$i_lists] ) ) {
								if ( $_GET["search_element_" . $number][$i_lists] == $get_cats[$i_ele]->term_id ) {
									$checked = ' checked';
								}
							}
						}
					}
				} elseif ( $default_value ) {
					if ( is_array( $default_value ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( $default_value[$i_lists] == $get_cats[$i_ele]->term_id ) {
								$checked = ' checked';
							}
						}
					}
				}

				$cat_cnt = '';
				if ( 'yes' == $showcnt ) {
					$cat_cnt = " (" . $term_cnt[$i_ele]->cnt . ") ";
				}

				$depth = '01';

				// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassを準備
				if ( 'b' === $get_data[$cols[5]] ) {
					$depth = str_pad( $get_cats[$i_ele]->depth, 2, '0', STR_PAD_LEFT );
				}

				/**
				 *
				 * checkboxのclassにかけるフィルター
				 *
				 */
				$class = 'feas_clevel_' . esc_attr( $depth );
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'parent'        => 0,
					'depth'         => 1,
					'check_cnt'     => $term_depth,
					'text'          => esc_attr( $get_cats[$i_ele]->name ),
					'value'         => esc_attr( $get_cats[$i_ele]->term_id ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $term_cnt[$i_ele]->cnt,
				);
				$class = apply_filters( 'feas_term_checkbox_class', $class, $args );

				/**
				 *
				 * checkboxのattrにかけるフィルター
				 *
				 */
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'parent'        => 0,
					'check_cnt'     => $term_depth,
					'depth'         => 1,
					'class'         => $class,
					'text'          => esc_attr( $get_cats[$i_ele]->name ),
					'value'         => esc_attr( $get_cats[$i_ele]->term_id ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $term_cnt[$i_ele]->cnt,
				);
				$attr = apply_filters( 'feas_term_checkbox_attr', $attr, $args );

				// Sanitize
				$ret_id   = esc_attr( "feas_{$manag_no}_{$number}_{$i_ele}" );
				$ret_name = esc_attr( "search_element_{$number}[]" );
				$ret_val  = esc_attr( $get_cats[$i_ele]->term_id );
				$ret_text = esc_html( $get_cats[$i_ele]->name . $cat_cnt );

				$html  = "<label for='{$ret_id}' class='{$class}'>";
				$html .= "<input id='{$ret_id}' type='checkbox' name='{$ret_name}' value='{$ret_val}' {$attr} {$checked} />";
				$html .= "<span>{$ret_text}</span>";
				$html .= "</label>\n";

				$total_cnt++;

				// 「要素内の並び順」が「自由記述」以外、または階層が0(=現在の階層のみ表示)以外の場合、子カテゴリを表示
				if ( 'b' !== $get_data[$cols[5]] && 0 !== $term_depth ) {

					// 子カテゴリ取得
					$args = array(
						'par_id'    => $get_cats[$i_ele]->term_id,
						'ele_class' => "feas_clevel_",
						'check_cnt' => $term_depth,
						'class_cnt' => 2,
						'nocnt'     => $nocnt,
						'exids'     => $exids,
						'sticky'    => $sticky,
						'showcnt'   => $showcnt,
						'taxonomy'  => $taxonomy,
						'par_no'    => $i_ele,
						'number'    => $number,
						'sp'        => $sp,
						'to'        => $order_by,
						'default_page' => $default_page,
					);
					$child_html = create_child_check( $args );
					$html .= $child_html;
				}

				/**
				 *
				 * 各チェックボックスごとにかけるフィルター
				 *
				 */
				$args = array(
					'manag_no'  => (int) $manag_no,
					'number'    => (int) $number,
					'cnt'       => (int) $i_ele,
					'par_no'    => 0,
					'parent'    => 0,
					'depth'     => 1,
					'value'     => esc_attr( $get_cats[$i_ele]->term_id ),
					'text'      => esc_attr( $get_cats[$i_ele]->name ),
					'class'     => $class,
					'attr'      => $attr,
					'checked'   => $checked,
					'show_post_cnt'  => $showcnt,
					'post_cnt'  => (int) $term_cnt[$i_ele]->cnt,
					'child_obj' => $child_html,
					//'add_class' => 'feas_has_child',
				);
				$html = apply_filters( 'feas_term_checkbox_html', $html, $args );

				// ループ前段に結合
				$ret_ele .= $html;
			}

			/**
			 *
			 * チェックボックスグループ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'parent'        => 0,
				'depth'         => 1,
				'show_post_cnt' => $showcnt,
				'ret_ele'       => $ret_ele,
			);
			$ret_ele = apply_filters( 'feas_term_checkbox_group_html', $ret_ele, $args );

			break;

		/**
		 *	ラジオボタン
		 */
		case 4:
		case 'd':

			/**
			 *	ラジオボタンの「未選択」の表示/非表示
			 */
			$noselect_status = get_option( $cols[31] . $manag_no . '_' . $number );
			if ( $noselect_status ) {

				$ret_ele .= "<label for='feas_" . esc_attr( $manag_no . "_" . $number ) . "_none' class='feas_clevel_01'>";
				$ret_ele .= "<input id='feas_" . esc_attr( $manag_no . "_" . $number ) . "_none' type='radio' name='search_element_" . esc_attr( $number ) . "' value='' />";
				$ret_ele .= "<span>" . esc_html( $noselect_text ) . "</span>";
				$ret_ele .= "</label>\n";
			}

			for ( $i_ele = 0, $cnt_ele = count( $get_cats ); $i_ele < $cnt_ele; $i_ele++ ) {

				// 0件タームは表示しない場合
				if ( $nocnt && $term_cnt[$i_ele]->cnt === "0" )
					continue;

				$checked = '';
				if ( isset( $_GET['fe_form_no'] ) && $_GET['fe_form_no'] == $manag_no ) {
					if ( isset( $_GET['search_element_' . $number] ) ) {
						if ( $_GET['search_element_' . $number] == $get_cats[$i_ele]->term_id ) {
							$checked = ' checked';
						}
					}
				} elseif ( $default_value ) {
					if ( is_array( $default_value ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( $default_value[$i_lists] == $get_cats[$i_ele]->term_id ) {
								$checked = ' checked';
							}
						}
					}
				}

				$cat_cnt = '';
				if ( "yes" == $showcnt ) {
					$cat_cnt = " (" . $term_cnt[$i_ele]->cnt . ") ";
				}

				$depth = '01';

				// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassを準備
				if ( 'b' === $get_data[$cols[5]] ) {
					$depth = str_pad( $get_cats[$i_ele]->depth, 2, '0', STR_PAD_LEFT );
				}

				/**
				 *
				 * radioのclassにかけるフィルター
				 *
				 */
				$class = 'feas_clevel_' . esc_attr( $depth );
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'parent'        => 0,
					'depth'         => 1,
					'check_cnt'     => $term_depth,
					'text'          => esc_attr( $get_cats[$i_ele]->name ),
					'value'         => esc_attr( $get_cats[$i_ele]->term_id ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $term_cnt[$i_ele]->cnt,
				);
				$class = apply_filters( 'feas_term_radio_class', $class, $args );

				/**
				 *
				 * radioのattrにかけるフィルター
				 *
				 */
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'parent'        => 0,
					'depth'         => 1,
					'check_cnt'     => $term_depth,
					'class'         => $class,
					'text'          => esc_attr( $get_cats[$i_ele]->name ),
					'value'         => esc_attr( $get_cats[$i_ele]->term_id ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $term_cnt[$i_ele]->cnt,
				);
				$attr = apply_filters( 'feas_term_radio_attr', $attr, $args );

				// Sanitize
				$ret_id   = esc_attr( "feas_{$manag_no}_{$number}_{$i_ele}" );
				$ret_name = esc_attr( "search_element_{$number}" );
				$ret_val  = esc_attr( $get_cats[$i_ele]->term_id );
				$ret_text = esc_html( $get_cats[$i_ele]->name . $cat_cnt );

				$html  = "<label for='{$ret_id}' class='{$class}'>";
				$html .= "<input id='{$ret_id}' type='radio' name='{$ret_name}' value='{$ret_val}' {$attr} {$checked} />";
				$html .= "<span>{$ret_text}</span>";
				$html .= "</label>\n";

				// 「要素内の並び順」が「自由記述」以外、または階層が0(=現在の階層のみ表示)以外の場合、子カテゴリを表示
				if ( 'b' !== $get_data[$cols[5]] && 0 !== $term_depth ) {

					// 子カテゴリ取得
					$args = [
						'par_id'    => $get_cats[$i_ele]->term_id,
						'ele_class' => "feas_clevel_",
						'check_cnt' => $term_depth,
						'class_cnt' => 2,
						'nocnt'     => $nocnt,
						'exids'     => $exids,
						'sticky'    => $sticky,
						'showcnt'   => $showcnt,
						'taxonomy'  => $taxonomy,
						'par_no'    => $i_ele,
						'number'    => $number,
						'sp'        => $sp,
						'to'        => $order_by,
						'default_page' => $default_page,
					];
					$child_html = create_child_radio( $args );
					$html .= $child_html;
				}

				/**
				 *
				 * 各ラジオボタンごとにかけるフィルター
				 *
				 */
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'parent'        => 0,
					'depth'         => 1,
					'class'         => $class,
					'attr'          => $attr,
					'value'         => esc_attr( $get_cats[$i_ele]->term_id ),
					'text'          => esc_attr( $get_cats[$i_ele]->name ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $term_cnt[$i_ele]->cnt,
					'child_obj'     => $child_html,
				);
				$html = apply_filters( 'feas_term_radio_html', $html, $args );

				// ループ前段に結合
				$ret_ele .= $html;
			}

			/**
			 *
			 * ラジオボタングループ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'parent'   => 0,
				'depth'    => 1,
				'show_post_cnt' => $showcnt,
				'ret_ele'       => $ret_ele,
			);
			$ret_ele = apply_filters( 'feas_term_radio_group_html', $ret_ele, $args );

			break;

		/**
		 *	フリーワード
		 */
		case 5:
		case 'e':

			$placeholder_data = '';
			$placeholder = '';
			$output_js = '';

			$placeholder_data = $get_data[$cols[30]];

			if ( $placeholder_data ) {
				$placeholder = ' placeholder="' . esc_attr( $placeholder_data ) . '"';
				$output_js = '<script>jQuery("#feas_' . esc_attr( $manag_no . '_' . $number ) . '").focus( function() { jQuery(this).attr("placeholder",""); }).blur( function() {
    jQuery(this).attr("placeholder", "' . esc_attr( $placeholder_data ) . '"); });</script>';
			}

			$s_keyword = '';
			if ( isset( $_GET['fe_form_no'] ) && $manag_no == $_GET['fe_form_no'] ) {
				if ( isset( $_GET['s_keyword_' . $number] ) ) {
					$s_keyword = $_GET['s_keyword_' . $number];
				}
			} elseif ( $default_value ) {
				if ( is_array( $default_value ) ) {
					for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
						if ( '' !== $s_keyword ) {
							$s_keyword .= ' ';
						}
						$s_keyword .= $default_value[$i_lists];
					}
				}
			}

			/**
			 *
			 * inputのclassにかけるフィルター
			 *
			 */
			$class = '';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$class = apply_filters( 'feas_term_freeword_class', $class, $args );

			/**
			 *
			 * inputのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$attr = apply_filters( 'feas_term_freeword_attr', $attr, $args );

			// Sanitize
			$ret_id   = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_name = esc_attr( "s_keyword_{$number}" );
			$ret_val  = esc_attr( stripslashes( $s_keyword ) );

			$html  = "<input type='text' name='{$ret_name}' id='{$ret_id}' class='{$class}' value='{$ret_val}' {$placeholder} {$attr} />";
			$html .= $output_js;

			/**
			 *
			 * AND/ORオプション
			 *
			 */
			$andor_html = '';
			$andor_ui_flag = $get_data[$cols[6]];

			if ( 'c' === $andor_ui_flag ) {

				// Sanitize
				$ret_6_id    = esc_attr( "feas_{$manag_no}_{$number}_andor" );
				$ret_6_name  = esc_attr( "feas_andor_{$number}" );

				/**
				 * Filter for class
				 */
				$ret_6_class = 'feas_freeword_andor';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_6_class = esc_attr( apply_filters( 'feas_freeword_andor_class', $ret_6_class, $args ) );

				/**
				 * Filter apply to the text "Exclude"
				 */
				$ret_6_or_text = 'OR';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_6_or_text  = esc_html( apply_filters( 'feas_freeword_andor_or_text', $ret_6_or_text, $args ) );

				$ret_6_and_text = 'AND';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_6_and_text = esc_html( apply_filters( 'feas_freeword_andor_and_text', $ret_6_and_text, $args ) );

				$checked_0 = $checked_1 = '';
				if ( isset( $_GET["{$ret_6_name}"] ) && 'a' === $_GET["{$ret_6_name}"] ) {
					$checked_0 = 'checked';
				} else {
					$checked_1 = 'checked';
				}

				$andor_html  = "<label for='{$ret_6_id}_0' class='{$ret_6_class}'>";
				$andor_html .= "<input type='radio' id='{$ret_6_id}_0' name='{$ret_6_name}' value='a' {$checked_0} />";
				$andor_html .= $ret_6_or_text;
				$andor_html .= "</label>";
				$andor_html .= "<label for='{$ret_6_id}_1' class='{$ret_6_class}'>";
				$andor_html .= "<input type='radio' id='{$ret_6_id}_1' name='{$ret_6_name}' value='b' {$checked_1} />";
				$andor_html .= $ret_6_and_text;
				$andor_html .= "</label>";
			}

			/**
			 *
			 * 除外オプション
			 *
			 */
			$exclude_html = '';
			$exclude_ui_flag = $get_data[$cols[52]];

			if ( '2' === $exclude_ui_flag ) {

				// Sanitize
				$ret_52_id    = esc_attr( "feas_{$manag_no}_{$number}_exclude" );
				$ret_52_name  = esc_attr( "feas_exclude_{$number}" );

				/**
				 * Filter for class
				 */
				$ret_52_class = 'feas_freeword_exclude';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_52_class = esc_attr( apply_filters( 'feas_freeword_exclude_class', $ret_52_class, $args ) );

				/**
				 * Filter apply to the text "Exclude"
				 */
				$ret_52_text = '除外';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_52_text = esc_html( apply_filters( 'feas_freeword_exclude_text', $ret_52_text, $args ) );

				$checked = '';
				if ( isset( $_GET["{$ret_52_name}"] ) && '1' === $_GET["{$ret_52_name}"] ) {
					$checked = 'checked';
				}

				$exclude_html  = "<label for='{$ret_52_id}' class='{$ret_52_class}'>";
				$exclude_html .= "<input type='checkbox' id='{$ret_52_id}' name='{$ret_52_name}' value='1' {$checked} />";
				$exclude_html .= $ret_52_text;
				$exclude_html .= "</label>";
			}

			/**
			 *
			 * 完全一致オプション
			 *
			 */
			$exact_html = '';
			$exact_ui_flag = $get_data[$cols[53]];
			if ( '2' === $exact_ui_flag ) {

				// Sanitize
				$ret_53_id    = esc_attr( "feas_{$manag_no}_{$number}_exact" );
				$ret_53_name  = esc_attr( "feas_exact_{$number}" );

				/*
				 * Filter for class
				 */
				$ret_53_class = 'feas_freeword_exact';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_53_class = esc_attr( apply_filters( 'feas_freeword_exact_class', $ret_53_class, $args ) );

				/*
				 * Filter apply to the text "Exclude"
				 */
				$ret_53_text = '完全一致';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_53_text = esc_html( apply_filters( 'feas_freeword_exact_text', $ret_53_text, $args ) );

				$checked = '';
				if ( isset( $_GET["{$ret_53_name}"] ) && '1' === $_GET["{$ret_53_name}"] ) {
					$checked = 'checked';
				}

				$exact_html  = "<label for='{$ret_53_id}' class='{$ret_53_class}'>";
				$exact_html .= "<input type='checkbox' id='{$ret_53_id}' name='{$ret_53_name}' value='1' {$checked} />";
				$exact_html .= $ret_53_text;
				$exact_html .= "</label>";
			}

			if ( 'c' === $andor_ui_flag || '2' === $exclude_ui_flag || '2' === $exact_ui_flag ) {

				$tmp_html  = '<div class="feas_inline_group">';
				$tmp_html .= $html;
				$tmp_html .= '<div class="feas_wrap_options">';
				$tmp_html .= $andor_html . $exclude_html . $exact_html;
				$tmp_html .= '</div>';
				$tmp_html .= "</div>";

				$html = $tmp_html;
			}

			// hiddenタグ出力
			if ( '' !== $get_data[$cols[20]] ) {
				$html .= create_specifies_the_key_element( $get_data, $number );
			}

			/**
			 *
			 * inputタグ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'attr'     => $attr,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$html = apply_filters( 'feas_term_freeword_group_html', $html, $args );

			$ret_ele .= $html;

			break;


		/**
		 *	グループ
		 */
		case 'f':

			break;

		/**
		 *	その他
		 */
		default:

			$s_keyword = '';
			if ( isset( $_GET['fe_form_no'] ) && $manag_no == $_GET['fe_form_no'] ) {
				if ( isset( $_GET['s_keyword_' . $number] ) ) {
					$s_keyword = $_GET['s_keyword_' . $number];
				}
			}

			/**
			 *
			 * inputのclassにかけるフィルター
			 *
			 */
			$class = '';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$class = apply_filters( 'feas_term_default_class', $class, $args );

			/**
			 *
			 * inputのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$attr = apply_filters( 'feas_term_default_attr', $attr, $args );

			// Sanitize
			$ret_id   = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_name = esc_attr( "s_keyword_{$number}" );
			$ret_val  = esc_attr( stripslashes( $s_keyword ) );

			$html  = "<input type='text' name='{$ret_name}' id='{$ret_id}' class='{$class}' value='{$ret_val}' {$placeholder} {$attr} />";

			/**
			 *
			 * inputタグ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'attr'     => $attr,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$html = apply_filters( 'feas_term_default_group_html', $html, $args );

			$ret_ele .= $html;

			break;
	}

	return $ret_ele;
}

/*============================
	カスタムフィールド（post_meta）のエレメント作成
 ============================*/
function create_meta_element( $get_data, $number ) {
	global $wpdb, $cols, $feadvns_show_count, $manag_no, $feadvns_include_sticky, $feadvns_search_target, $feadvns_exclude_id, $feadvns_default_cat, $feadvns_default_page, $feadvns_exclude_term_id;

// 変更
// 現在の年月を取得
date_default_timezone_set('Asia/Tokyo');
$current_year_month = date('Ym');
// 変更　ここまで

	$nocnt = false;
	$get_key = $get_unit = $kugiriFlag = $exclude_post_ids = $exclude_id = $exclude_term_id = $default_page = '';
	$acfChoices = $sp = $savedValues = $metaValues = array();

	/**
	 * Smart Custom Fields 関連
	 * 1=真偽値　2=関連する投稿　3=関連するターム
	 */
	$cf_scf = get_option( $cols[33] . $manag_no . "_" . $number );

	// 真のチェックボックスを表示するかどうか
	$show_shingi = get_option( $cols[41] . $manag_no . "_" . $number );

	// 偽のチェックボックスを表示するかどうか
	$show_shingi_alt = get_option( $cols[42] . $manag_no . "_" . $number );

	/**
	 * Advanced Custom Fields 関連
	 */
	$acf_flag = $get_data[$cols[38]];

	// キー取得（meta_を除いた部分）
	$get_key = mb_substr( $get_data[$cols[2]], 5, mb_strlen( $get_data[$cols[2]] ) );

	// ACF複数選択形式で登録された値の場合は表記一覧を取得
	if ( $get_key ) {
		$sql = <<<SQL
SELECT post_content
FROM {$wpdb->posts}
WHERE post_excerpt = %s
LIMIT 1
SQL;
		$sql = $wpdb->prepare( $sql, $get_key );
		$acfData = $wpdb->get_var( $sql );
		if ( $acfData ) {
			$acfData = maybe_unserialize( $acfData );
			if ( array_key_exists( 'choices', $acfData ) ) {
				$acfChoices = $acfData['choices'];
			}
		}
	}

	/**
	 *	範囲検索
	 */
	$rangeValue = $get_data[$cols[16]];
	$rangeFlag = false;
	if ( 1 <= (int) $rangeValue ) {
		$rangeFlag = true;
	}

	/**
	 *	数値or文字フラグ
	 */
	$range_as = "";
	if ( isset( $get_data[$cols[29]] ) ) {
		switch ( $get_data[$cols[29]] ) {
			case 'int':
				$range_as = "+0";
				break;
		}
	}

	// 単位を付与
	if ( $get_data[$cols[17]] . $number != "" ) {
		$cfUnit = $get_data[$cols[17]];
	}

	$kugiriFlag = $get_data[$cols[18]];

	// 検索対象のpost_typeを取得
	$target_pt_tmp = get_option( $feadvns_search_target . $manag_no );
	if ( $target_pt_tmp ) {
		$target_pt = "'" . implode( "','", (array) $target_pt_tmp ) . "'";
	} else {
		$target_pt = "'post'";
	}

	// 固定記事(Sticky Posts)を検索対象から省く場合、カウントに含めない
	$target_sp = get_option( $feadvns_include_sticky . $manag_no );
	if ( 'yes' != $target_sp ) {
		$sticky = get_option( 'sticky_posts' );
		if ( ! empty( $sticky ) ) {
			$sp = array_merge( $sp, $sticky ); // 除外IDにマージ
		}
	}

	// 投稿ステータス
	if ( in_array( 'attachment', (array) $target_pt_tmp ) ) {
		$post_status = "'publish', 'inherit'";
	} else {
		$post_status = "'publish'";
	}

	// 固定条件 > タクソノミ／ターム
	$fixed_term = get_option( $feadvns_default_cat . $manag_no );

	// 固定条件 > 親ページ
	$default_page = get_option( $feadvns_default_page . $manag_no );
	if ( $default_page ) {
		$default_page = implode( ',', (array) $default_page );
		$default_page = " AND p.post_parent IN (" . esc_sql( $default_page ) . ")";
	}

	// 検索条件に件数を表示
	$showcnt = get_option( $feadvns_show_count . $manag_no );

	// 除外する記事ID
	$exclude_id = get_option( $feadvns_exclude_id . $manag_no );
	if ( $exclude_id ) {
		$sp = array_merge( $sp, $exclude_id ); // 除外IDにマージ
	}

	// 検索結果から除外するタームID（全体）
	// タームごとのカウントに反映するため
	$exclude_term_id = get_option( $feadvns_exclude_term_id . $manag_no );
	if ( $exclude_term_id ) {
		$args['cat']      = $exclude_term_id;
		$args['format']   = 'array';
		$args['mode']     = 'exclude';
		$dcat['orderby']  = '';
		$exclude_post_ids = create_where_single_cat( $args );
	}

	// 除外タームのSQLを構成
	if ( $exclude_post_ids ) {
		$sp = array_merge( $sp, $exclude_post_ids ); // 除外IDにマージ
	}

	/**
	 *	除外IDをカンマ区切りにする
	 */
	if ( $sp ) {
		$sp = implode( ',', $sp );
	}

	/**
	 *	0件のカスタムフィールドを表示しない設定の場合
	 */
/*
	if ( isset( $get_data[$cols[33]] ) && $get_data[$cols[33]] == 'no' ) {
		$nocnt = true;
	}
*/

	// 条件内の並び順
	$order_by = "pm.meta_id";

	if ( isset( $get_data[$cols[5]] ) ) {
		switch ( (string) $get_data[$cols[5]] ) {

			// 開始番号は、management-viewの「並び順」ドロップダウンにおいて、「ターム（0〜7）」「年月（8〜9）」に続くもの
			case '10':
			case '11':
			case 'h':
				$order_by = "pm.meta_id";
				break;
			case '12':
			case '13':
			case '14':
			case '15':
			case 'i':
				$order_by = "REPLACE( pm.meta_value, ',', '' )";
				break;
			case '16':
			case 'j':
				$order_by = "RAND()";
				break;
			default:
				$order_by = "pm.meta_id";
				break;
		}
	}

	/**
	 *	条件内の並び順 数値or文字列
	 */
	$sort_as = "";

	if ( isset( $get_data[$cols[34]] ) ) {
		switch ( $get_data[$cols[34]] ) {

			case 'int':
				$sort_as = "+0";
				break;
			case 'str':
				$sort_as = "";
				break;
			default:
				$sort_as = "";
				break;
		}
	}

	/**
	 *	条件内の並び順 昇順/降順
	 */
	$order = " ASC";

	if ( isset( $get_data[$cols[35]] ) ) {
		switch ( $get_data[$cols[35]] ) {

			case 'asc':
				$order = " ASC";
				break;
			case 'desc':
				$order = " DESC";
				break;
			default:
				$order = " ASC";
				break;
		}
	}

	/**
	 *	キャッシュから取得／ない場合は実行してキャッシュ保存
	 */

	// 「要素内の並び順」が「自由記述」の場合は、ターム一覧をDBから呼び出す代わりに記述内容で配列$savedValuesを構成
	// キャッシュは利用しない
	if ( 'b' === $get_data[$cols[5]] ) {

		$options = $get_data[$cols[36]];

		if ( ! empty( $options ) ) {

			$savedValues = array();

			// 行数分ループを回す
			for ( $i = 0; $cnt = count( $options ), $i < $cnt; $i++ ) {

				if ( empty( $options[$i] ) )
					continue;

				// 自由記述において、コロンの右側にACFの記述どおりの内容をダブルクォーテーションで囲んで記述した場合の対処
				// v1.9.3以降、コロンの右側にはACFの値のみ記述すればOK
				$valueTmp = array();
				$valueTmp = explode( ':', $options[$i]['value'] );
				$valueTmp = trim( $valueTmp[0] ); // コロンの左側の値のみ取得
				$savedValues[$i]['meta_value'] = $valueTmp;

				// 「:」の前半は表記
				$savedValues[$i]['text'] = $options[$i]['text'];

				// 階層
				$savedValues[$i]['depth'] = $options[$i]['depth'];
			}
		}
	}

	// 「自由記述」ではない場合
	else {

		// キャッシュがない場合
		if ( false === ( $savedValues = feas_cache_judgment( $manag_no, 'post_meta', $get_key ) ) ) {
			$sql  = " SELECT meta_id, post_id, meta_value FROM {$wpdb->postmeta} AS pm";
			$sql .= " LEFT JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id";
			$sql .= " WHERE pm.meta_key = '" . esc_sql( $get_key ) . "'";
			$sql .= " AND pm.meta_value IS NOT NULL";
			$sql .= " AND pm.meta_value != ''";
			$sql .= " AND p.post_type IN( {$target_pt} )";
			$sql .= " AND p.post_status IN ( {$post_status} )";
			$sql .= " GROUP BY pm.meta_value";
			$sql .= " ORDER BY " . $order_by . $sort_as . " " . $order;
			$savedValues = $wpdb->get_results( $sql, ARRAY_A );
			feas_cache_create( $manag_no, 'post_meta', $get_key, $savedValues );
		}
	}

	if ( get_option( $cols[22] . $manag_no . "_" . $number ) == 'yes' ) {
		$fe_limit_free_input = true;
		$get_data[$cols[4]] = 'cf_limit_keyword';
	}

	if ( is_array( $savedValues ) ) {
		$metaValues = $metaLabels = array();
		foreach( $savedValues as $k => $v ) {

			// ACFなどシリアライズされた値を分解して選択肢に追加
			$unserValue = maybe_unserialize( $v['meta_value'] );

			// 複数選択形式で保存された値
			if ( is_array( $unserValue ) ) {
				foreach( $unserValue as $uv ) {
					if ( array_key_exists( $uv, $acfChoices ) ) {
						$metaLabels[] = $acfChoices[$uv];
					} else {
						$metaLabels[] = $uv;
					}
					$metaValues[] = $uv;
				}

			// それ以外
			} else {
				if ( array_key_exists( $unserValue, $acfChoices ) ) {
					$metaLabels[] = $acfChoices[$unserValue];
				} else {
					$metaLabels[] = $unserValue;
				}
				$metaValues[] = $unserValue;
			}
		}

		// 重複削除
		$metaLabels = array_unique( $metaLabels );
		$metaValues = array_unique( $metaValues );
		// インデックス振り直し
		$metaLabels = array_values( $metaLabels );
		$metaValues = array_values( $metaValues );

		// 真偽値の場合、値の降順にする（ 1 = 真 を先頭に）
		if ( '1' === $cf_scf ) {
			rsort( $metaValues );
		}
	}

	// 検索条件の選択肢の数
	$cntValues = count( $metaValues );

	/**
	 *	件数を取得してキャッシュ保存
	 */
	if ( $metaValues ) {

		$cf_cnt = array();
		foreach( $metaValues as $cf_data ) {
			if ( false === ( $cnt = feas_cache_judgment( $manag_no, 'cf_cnt_' . $cf_data, false ) ) ) {
				$sql  = " SELECT count( DISTINCT( meta_value ) ) AS cnt FROM {$wpdb->postmeta} AS pm";
				$sql .= " INNER JOIN {$wpdb->posts} AS p ON pm.post_id = p.ID";
				if ( $fixed_term ) $sql .= " INNER JOIN {$wpdb->term_relationships} AS tr ON p.ID = tr.object_id";
				$sql .= " WHERE 1=1";
				$sql .= " AND meta_key = '" . esc_sql( $get_key ) . "'";
				if ( $acf_flag ) {
					$cf_data = esc_sql( $cf_data );
					$sql .= " AND ( meta_value LIKE '%\"{$cf_data}\"%' )";
				} else {
					if ( $rangeFlag ) {
						if ( '1' === $rangeValue ) {
							$sql .= " AND REPLACE( meta_value, ',', '' ){$range_as} < REPLACE( '" . esc_sql( $cf_data ) . "', ',', '' )";
						} elseif ( '2' === $rangeValue ) {
							$sql .= " AND REPLACE( meta_value, ',', '' ){$range_as} <= REPLACE( '" . esc_sql( $cf_data ) . "', ',', '' )";
						} elseif ( '3' === $rangeValue ) {
							$sql .= " AND REPLACE( meta_value, ',', '' ){$range_as} >= REPLACE( '" . esc_sql( $cf_data ) . "', ',', '' )";
						} elseif ( '4' === $rangeValue ) {
							$sql .= " AND REPLACE( meta_value, ',', '' ){$range_as} > REPLACE( '" . esc_sql( $cf_data ) . "', ',', '' )";
						}
					} else {
						$sql .= " AND meta_value = '" . esc_sql( $cf_data ) . "'";
					}
				}
				$sql .= " AND pm.meta_value != ''";
				$sql .= " AND pm.meta_value IS NOT NULL";
				if ( $sp ) $sql .= " AND p.ID NOT IN ( {$sp} )";
				if ( $default_page ) {
					$sql .= $default_page;
				}
				if ( $fixed_term ) $sql .= " AND tr.term_taxonomy_id = " . esc_sql( $fixed_term );
				$sql .= " AND p.post_type IN ( {$target_pt} )";
				$sql .= " AND p.post_status IN ( {$post_status} )";
				$cnt = $wpdb->get_row( $sql );
				feas_cache_create( $manag_no, 'cf_cnt_' . $cf_data, false, $cnt );
			}
			$cf_cnt[] = $cnt;
		}
	}

	// 未選択時の文字列
	$noselect_text = $get_data[$cols[27]];

	/**
	 *	デフォルト値
	 */
	$default_value = $get_data[$cols[39]];
	if ( '' !== $default_value ) {
		$default_value = explode( ',', $default_value );
	}

	$ret_ele = '';

	switch ( (string) $get_data[$cols[4]] ) {

		/**
		 *	ドロップダウン
		 */
		case '1':
		case 'a':

			$ret_opt = '';

			for ( $i = 0; $i < $cntValues; $i++ ) {

				// 0件のカスタムフィールドは表示しない場合
				if ( $nocnt && $cf_cnt[$i]->cnt == 0 )
					continue;

				// 真の選択肢を表示or非表示
				if ( '1' === $cf_scf && '1' === $metaValues[$i] && '1' !== $show_shingi )
					continue;

				// 偽の選択肢を表示or非表示
				if ( '1' === $cf_scf && '0' === $metaValues[$i] && '1' !== $show_shingi_alt )
					continue;

				$selected = '';
				if ( isset( $_GET['fe_form_no'] ) && $_GET['fe_form_no'] == $manag_no ) {
					if ( isset( $_GET['search_element_' . $number] ) ) {
						if ( $_GET['search_element_' . $number ] == $metaValues[$i] ) {
							$selected = ' selected';
						}
					}
				} elseif ( $default_value ) {
					if ( is_array( $default_value ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( $default_value[$i_lists] == $metaValues[$i] ) {
								$selected = ' selected';
							}
						}
					}
				}

				// 「要素内の並び順」が「自由記述」以外の場合
				if ( 'b' !== $get_data[$cols[5]] ) {

					// 真偽値の場合
					if ( '1' === $cf_scf ) {

						// 真の場合の文字列
						//$cfText = get_option( $cols[25] . $manag_no . "_" . $number );

						if ( '1' === $metaValues[$i] ) {
							// 真の場合の文字列
							$cfText = get_option( $cols[25] . $manag_no . "_" . $number );
						} else {
							// 偽の場合の文字列
							$cfText = get_option( $cols[40] . $manag_no . "_" . $number );
						}

					// 「関連する投稿」
					} elseif ( '2' === $cf_scf ) {

						$relatedPost = get_post( $metaValues[$i] );
						$cfText = $relatedPost->post_title;

					// 「関連するターム」
					} elseif ( '3' === $cf_scf ) {

						$relatedTerm = get_term( $metaValues[$i] );
						$cfText = $relatedTerm->name;

					} else {

						if ( 'yes' === $kugiriFlag && is_numeric( $metaValues[$i] ) ) {
							$cfText = number_format( $metaValues[$i] );
						} else {
							$cfText = $metaLabels[$i];
						}
					}

					if ( '0' === $get_data[$cols[26]] ) {
						$cfText = $cfUnit . $cfText; // 単位が前
					} else {
						$cfText = $cfText . $cfUnit; // 単位が後
					}

				} else {
					$cfText = $savedValues[$i]['text'];
				}

				if ( 'yes' == $showcnt ) {
					$text = $cfText . " (" . $cf_cnt[$i]->cnt . ") ";
				} else {
					$text = $cfText;
				}

				$depth = '01';
				$indentSpace = '';

				// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassとインデントを準備
				if ( 'b' === $get_data[$cols[5]] ) {
					if ( 1 !== $savedValues[$i]['depth'] ) {
						$depth = str_pad( $savedValues[$i]['depth'], 2, '0', STR_PAD_LEFT );
						for ( $i_depth = 1; $i_depth < $savedValues[$i]['depth']; $i_depth++ ) {
							$indentSpace .= '&nbsp;&nbsp;';
						}
					}
				}

				/**
				 *
				 * selectのclassにかけるフィルター
				 *
				 */
				$class = 'feas_clevel_' . esc_attr( $depth );
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i,
					'text'          => esc_attr( $cfText ),
					'value'         => esc_attr( $metaValues[$i] ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $cf_cnt[$i]->cnt,
				);
				$class = apply_filters( 'feas_meta_dropdown_class', $class, $args );

				/**
				 *
				 * 各optionのattrにかけるフィルター
				 *
				 */
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i,
					'class'         => $class,
					'text'          => esc_attr( $cfText ),
					'value'         => esc_attr( $metaValues[$i] ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $cf_cnt[$i]->cnt,
				);
				$attr = apply_filters( 'feas_meta_dropdown_attr', $attr, $args );

				$ret_id   = esc_attr( "feas_{$manag_no}_{$number}_{$i}" );
				$ret_val  = esc_attr( $metaValues[$i] );
				$ret_txt  = esc_html( $text );

				$html  = "<option id='{$ret_id}' class='{$class}' value='{$ret_val}' {$attr} {$selected}>";
				$html .= $indentSpace . $ret_txt;
				$html .= "</option>\n";

				/**
				 *
				 * 各optionごとにかけるフィルター
				 *
				 */
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i,
					'depth'         => (int) $depth,
					'class'         => $class,
					'attr'          => $attr,
					'text'          => esc_attr( $cfText ),
					'value'         => esc_attr( $metaValues[$i] ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $cf_cnt[$i]->cnt,
					'indent'        => $indentSpace,
					'unit'          => $cfUnit,
					'sep'           => $kugiriFlag,
				);
				$html = apply_filters( 'feas_meta_dropdown_html', $html, $args );

				$ret_opt .= $html;

			}

			/**
			 *
			 * selectのclassにかけるフィルター
			 *
			 */
			$class = 'feas_meta_dropdown';
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'ret_opt'       => $ret_opt,
				'show_post_cnt' => $showcnt,
			);
			$class = apply_filters( 'feas_meta_dropdown_group_class', $class, $args );

			/**
			 *
			 * selectのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'class'         => $class,
				'ret_opt'       => $ret_opt,
				'show_post_cnt' => $showcnt,
			);
			$attr = apply_filters( 'feas_meta_dropdown_group_attr', $attr, $args );

			// Sanitize
			$ret_name = esc_attr( "search_element_{$number}" );
			$ret_id   = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_txt  = esc_html( $noselect_text );

			$ret_ele .= "<select name='{$ret_name}' id='{$ret_id}' class='{$class}' {$attr}>\n";
			$ret_ele .= "<option id='{$ret_id}_none' value=''>";
			$ret_ele .= $ret_txt;
			$ret_ele .= "</option>\n";
			$ret_ele .= $ret_opt;
			$ret_ele .= "</select>\n";

			/**
			 *
			 * select全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'class'         => $class,
				'attr'          => $attr,
				'ret_opt'       => $ret_opt,
				'show_post_cnt' => $showcnt,
			);
			$ret_ele = apply_filters( 'feas_meta_dropdown_group_html', $ret_ele, $args );

			break;

		/**
		 *	セレクトボックス
		 */
		case '2':
		case 'b':

			$ret_opt = '';
			$selected_cnt = 0;

			for ( $i = 0; $i < $cntValues; $i++ ) {

				// 0件のカスタムフィールドは表示しない場合
				if ( $nocnt && $cf_cnt[$i]->cnt == 0 )
					continue;

				// 真の選択肢を表示or非表示
				if ( '1' === $cf_scf && '1' === $metaValues[$i] && '1' !== $show_shingi )
					continue;

				// 偽の選択肢を表示or非表示
				if ( '1' === $cf_scf && '0' === $metaValues[$i] && '1' !== $show_shingi_alt )
					continue;

				$selected = '';
				if ( isset( $_GET['fe_form_no'] ) && $_GET['fe_form_no'] == $manag_no ) {
					if ( isset( $_GET["search_element_" . $number] ) && is_array( $_GET['search_element_' . $number] ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $_GET["search_element_" . $number] ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( isset( $_GET["search_element_" . $number][$i_lists] ) ) {

								// シリアライズされたデータなどの場合
								$searchQuery = stripslashes( $_GET["search_element_" . $number][$i_lists] );

								if ( $searchQuery == $metaValues[$i] ) {
									$selected = ' selected';
									$selected_cnt++;
								}
							}
						}
					}
				} elseif ( $default_value ) {
					if ( is_array( $default_value ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( $default_value[$i_lists] == $metaValues[$i] ) {
								$selected = ' selected';
							}
						}
					}
				}

				// 「要素内の並び順」が「自由記述」以外の場合
				if ( 'b' !== $get_data[$cols[5]] ) {

					// 真偽値の場合
					if ( '1' === $cf_scf ) {

						// 真の場合の文字列
						//$cfText = get_option( $cols[25] . $manag_no . "_" . $number );

						if ( '1' === $metaValues[$i] ) {
							// 真の場合の文字列
							$cfText = get_option( $cols[25] . $manag_no . "_" . $number );
						} else {
							// 偽の場合の文字列
							$cfText = get_option( $cols[40] . $manag_no . "_" . $number );
						}

					// 「関連する投稿」
					} elseif ( '2' === $cf_scf ) {

						$relatedPost = get_post( $metaValues[$i] );
						$cfText = $relatedPost->post_title;

					// 「関連するターム」
					} elseif ( '3' === $cf_scf ) {

						$relatedTerm = get_term( $metaValues[$i] );
						$cfText = $relatedTerm->name;

					} else {

						if ( 'yes' === $kugiriFlag && is_numeric( $metaValues[$i] ) ) {
							$cfText = number_format( $metaValues[$i] );
						} else {
							$cfText = $metaLabels[$i];
						}
					}

					if ( '0' === $get_data[$cols[26]] ) {
						$cfText = $cfUnit . $cfText; // 単位が前
					} else {
						$cfText = $cfText . $cfUnit; // 単位が後
					}

				} else {
					$cfText = $savedValues[$i]['text'];
				}

				if ( 'yes' === $showcnt ) {
					$text = $cfText . " ({$cf_cnt[$i]->cnt}) ";
				} else {
					$text = $cfText;
				}

				$depth = '01';
				$indentSpace = '';

				// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassとインデントを準備
				if ( 'b' === $get_data[$cols[5]] ) {
					if ( '1' !== $savedValues[$i]['depth'] ) {
						$depth = str_pad( $savedValues[$i]['depth'], 2, '0', STR_PAD_LEFT );
						for ( $i_depth = 1; $i_depth < $savedValues[$i]['depth']; $i_depth++ ) {
							$indentSpace .= '&nbsp;&nbsp;';
						}
					}
				}

				/**
				 *
				 * optionのclassにかけるフィルター
				 *
				 */
				$class = 'feas_clevel_' . esc_attr( $depth );
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i,
					'text'          => esc_attr( $cfText ),
					'value'         => esc_attr( $metaValues[$i] ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $cf_cnt[$i]->cnt,
				);
				$class = apply_filters( 'feas_meta_multiple_class', $class, $args );

				/**
				 *
				 * 各optionのattrにかけるフィルター
				 *
				 */
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i,
					'class'         => $class,
					'text'          => esc_attr( $cfText ),
					'value'         => esc_attr( $metaValues[$i] ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => $cf_cnt[$i]->cnt,
				);
				$attr = apply_filters( 'feas_meta_multiple_attr', $attr, $args );

				$ret_id   = esc_attr( "feas_{$manag_no}_{$number}_{$i}" );
				$ret_val  = esc_attr( $metaValues[$i] );
				$ret_txt  = esc_html( $text );

				$html  = "<option id='{$ret_id}' class='{$class}' value='{$ret_val}' {$attr} {$selected}>";
				$html .= $indentSpace . $ret_txt;
				$html .= "</option>\n";

				/**
				 *
				 * 各オプションごとにかけるフィルター
				 *
				 */
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i,
					'depth'         => (int) $depth,
					'class'         => $class,
					'attr'          => $attr,
					'value'         => esc_attr( $metaValues[$i] ),
					'text'          => esc_attr( $cfText ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $cf_cnt[$i]->cnt,
					'indent'        => $indentSpace,
					'unit'          => $cfUnit,
					'sep'           => $kugiriFlag,
				);
				$html = apply_filters( 'feas_meta_multiple_html', $html, $args );

				// ループ前段に結合
				$ret_opt .= $html;
			}

			// iOSではセレクトボックスが1行にまとめられてしまい、selectedが1件も付いていないと「0項目」と表示されてしまい、未選択時テキストが表示されないため。
			$selected = '';
			if ( 0 === $selected_cnt ) {
				if ( wp_is_mobile() ) {
					$selected = ' selected';
				}
			}

			/**
			 *
			 * Multipleのclassにかけるフィルター
			 *
			 */
			$class = 'feas_meta_multiple';
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'ret_opt'       => $ret_opt,
				'show_post_cnt' => $showcnt,
			);
			$class = apply_filters( 'feas_meta_multiple_group_class', $class, $args );

			/**
			 *
			 * Multipleのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'class'         => $class,
				'ret_opt'       => $ret_opt,
				'show_post_cnt' => $showcnt,
			);
			$attr = apply_filters( 'feas_meta_multiple_group_attr', $attr, $args );

			// Sanitize
			$ret_name = esc_attr( "search_element_{$number}[]" );
			$ret_id   = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_txt  = esc_html( $noselect_text );

			$html  = "<select name='{$ret_name}' id='{$ret_id}' class='{$class}' size='5' multiple='multiple' {$attr}>\n";
			$html .= "<option id='{$ret_id}_none' value='' {$selected}>";
			$html .= $ret_txt;
			$html .= "</option>\n";
			$html .= $ret_opt;
			$html .= "</select>\n";

			/**
			 *
			 * セレクトボックス全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'class'         => $class,
				'attr'          => $attr,
				'ret_opt'       => $ret_opt,
				'show_post_cnt' => $showcnt,
			);
			$html = apply_filters( 'feas_meta_multiple_group_html', $html, $args );

			// ループ前段に結合
			$ret_ele .= $html;

			break;

		/**
		 *	チェックボックス
		 */
		case '3':
		case 'c':

			for ( $i = 0; $i < $cntValues; $i++ ) {

				// 0件のカスタムフィールドは表示しない場合
				if ( $nocnt && $cf_cnt[$i]->cnt == 0 )
					continue;

				// 真の選択肢を表示or非表示
				if ( '1' === $cf_scf && '1' === $metaValues[$i] && '1' !== $show_shingi )
					continue;

				// 偽の選択肢を表示or非表示
				if ( '1' === $cf_scf && '0' === $metaValues[$i] && '1' !== $show_shingi_alt )
					continue;


				$checked = '';
				if ( isset( $_GET['fe_form_no'] ) && $_GET['fe_form_no'] == $manag_no ) {
					if ( isset( $_GET["search_element_" . $number] ) && is_array( $_GET['search_element_' . $number] ) ) {

						for ( $i_lists = 0, $cnt_lists = count( $_GET["search_element_" . $number] ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( isset( $_GET["search_element_" . $number][$i_lists] ) ) {

								// シリアライズされたデータなどの場合
								$searchQuery = stripslashes( $_GET["search_element_" . $number][$i_lists] );

								if ( $searchQuery == $metaValues[$i] )
									$checked = ' checked';
							}
						}
					}
				} elseif ( $default_value ) {
					if ( is_array( $default_value ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( $default_value[$i_lists] == $metaValues[$i] ) {
								$checked = ' checked';
							}
						}
					}
				}

				// 要素内の並び順 = カスタム「以外」
				if ( 'b' != $get_data[$cols[5]] ) {

					// 真偽値の場合
					if ( '1' === $cf_scf ) {

						if ( '1' === $metaValues[$i] ) {
							// 真の場合の文字列
							$cfText = get_option( $cols[25] . $manag_no . "_" . $number );
						} else {
							// 偽の場合の文字列
							$cfText = get_option( $cols[40] . $manag_no . "_" . $number );
						}

					// 「関連する投稿」
					} elseif ( '2' === $cf_scf ) {

						$relatedPost = get_post( $metaValues[$i] );
						$cfText = $relatedPost->post_title;

					// 「関連するターム」
					} elseif ( '3' === $cf_scf ) {

						$relatedTerm = get_term( $metaValues[$i] );
						$cfText = $relatedTerm->name;

					} else {

						if ( 'yes' == $kugiriFlag && is_numeric( $metaValues[$i] ) ) {
							$cfText = number_format( $metaValues[$i] );
						} else {
							$cfText = $metaLabels[$i];
						}
					}

					if ( '0' === $get_data[$cols[26]] ) {
						$cfText = $cfUnit . $cfText; // 単位が前
					} else {
						$cfText = $cfText . $cfUnit; // 単位が後
					}

				} else {
					$cfText = $savedValues[$i]['text'];
				}

				if ( 'yes' == $showcnt ) {
					$text = $cfText . " (" . $cf_cnt[$i]->cnt . ") ";
				} else {
					$text = $cfText;
				}

				$depth = '01';

				// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassを準備
				if ( 'b' === $get_data[$cols[5]] ) {
					$depth = str_pad( $savedValues[$i]['depth'], 2, '0', STR_PAD_LEFT );
				}

				/**
				 *
				 * チェックボックスのclassにかけるフィルター
				 *
				 */
				$class = 'feas_clevel_' . esc_attr( $depth );
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i,
					'text'          => esc_attr( $cfText ),
					'value'         => esc_attr( $metaValues[$i] ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $cf_cnt[$i]->cnt,
				);
				$class = apply_filters( 'feas_archive_checkbox_class', $class, $args );

				/**
				 *
				 * 各チェックボックスのattrにかけるフィルター
				 *
				 */
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i,
					'class'         => $class,
					'text'          => esc_attr( $cfText ),
					'value'         => esc_attr( $metaValues[$i] ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $cf_cnt[$i]->cnt,
				);
				$attr = apply_filters( 'feas_meta_checkbox_attr', $attr, $args );

				// Sanitize
				$ret_id   = esc_attr( "feas_{$manag_no}_{$number}_{$i}" );
				$ret_name = esc_attr( "search_element_{$number}[]" );
				$ret_val  = esc_attr( $metaValues[$i] );
				$ret_text = esc_html( $text );

				$html  = "<label for='{$ret_id}' class='{$class}'>";
				$html .= "<input id='{$ret_id}' type='checkbox' name='{$ret_name}' value='{$ret_val}' {$attr} {$checked} />";
				// 変更
				// $html .= "<span>{$ret_text}</span>";

//エリアのチェックボックス
if ( $get_data[$cols[2]] == 'meta_cf__fudo_cmn_area' ) {

	$cf__fudo_cmn_area_prefecture = '';
	$cf__fudo_cmn_area_city = '';
	$cf__fudo_cmn_area_town = '';
	
	$cf__fudo_cmn_area_prefecture = $wpdb->get_var($wpdb->prepare("SELECT prefecture_name FROM wp_fudo_area_prefectures WHERE prefecture_id = %d", $ret_val));
	$cf__fudo_cmn_area_city = $wpdb->get_var($wpdb->prepare("SELECT city_name FROM wp_fudo_area_cities WHERE city_id = %d", $ret_val));
	$cf__fudo_cmn_area_town = $wpdb->get_var($wpdb->prepare("SELECT town_name FROM wp_fudo_area_towns WHERE town_id = %d", $ret_val));
	$cf__fudo_cmn_area = $cf__fudo_cmn_area_prefecture.$cf__fudo_cmn_area_city.$cf__fudo_cmn_area_town;
	
					$html .= "<span>{$cf__fudo_cmn_area}</span>";
	} elseif ( $get_data[$cols[2]] == 'meta_cf__fudo_cmn_traffic' ) {
	//交通のチェックボックス
	$cf__fudo_cmn_traffic_prefecture = '';
	$cf__fudo_cmn_traffic_line = '';
	$cf__fudo_cmn_traffic_station = '';
	
	$cf__fudo_cmn_traffic_prefecture = $wpdb->get_var($wpdb->prepare("SELECT prefecture_name FROM wp_fudo_area_prefectures WHERE prefecture_id = %d", $ret_val));
	$cf__fudo_cmn_traffic_line = $wpdb->get_var($wpdb->prepare("SELECT line_name FROM wp_fudo_traffic_lines WHERE line_cd = %d", $ret_val));
	$cf__fudo_cmn_traffic_station = $wpdb->get_var($wpdb->prepare("SELECT station_name FROM wp_fudo_traffic_stations WHERE station_cd = %d", $ret_val));
	
	
	// line_cd を元に line_name を取得
	
	// line_cd を元に最初の駅の情報を取得
	// $cf__fudo_cmn_traffic_station = $wpdb->get_row($wpdb->prepare(
	// 		"SELECT station_cd, station_name FROM wp_fudo_traffic_stations WHERE line_cd = %d LIMIT 1",
	// 		$ret_val
	// ), ARRAY_A);
	
	// if ($cf__fudo_cmn_traffic_station) {
	// 		$station_cd = $cf__fudo_cmn_traffic_station['station_cd'];
	// 		$station_name = $cf__fudo_cmn_traffic_station['station_name'];
	// } else {
	// 		$station_cd = null;
	// 		$station_name = null;
	// }
		
	
	//$cf__fudo_cmn_traffic_line = $wpdb->get_var($wpdb->prepare("SELECT line_cd FROM wp_fudo_traffic_stations WHERE pref_cd = %d", $ret_val));
	//$cf__fudo_cmn_traffic_station = $wpdb->get_var($wpdb->prepare("SELECT station_cd, station_name FROM wp_fudo_traffic_stations WHERE line_cd = %d", $ret_val));
	//$cf__fudo_cmn_traffic_prefecture = $wpdb->get_var($wpdb->prepare("SELECT prefecture_id, prefecture_name FROM wp_fudo_area_prefectures", $ret_val));
	//$cf__fudo_cmn_traffic_line = $wpdb->get_var($wpdb->prepare("SELECT line_cd, line_name FROM wp_fudo_traffic_lines WHERE line_cd IN (SELECT line_cd FROM wp_fudo_traffic_stations WHERE pref_cd = %d)", $ret_val));
	//$cf__fudo_cmn_traffic_station = $wpdb->get_var($wpdb->prepare("SELECT station_cd, station_name FROM wp_fudo_traffic_stations WHERE line_cd = %d", $ret_val));
	$cf__fudo_cmn_traffic = $cf__fudo_cmn_traffic_prefecture.$cf__fudo_cmn_traffic_line.$cf__fudo_cmn_traffic_station;
	
					$html .= "<span>{$cf__fudo_cmn_traffic}</span>";
	} else {
					$html .= "<span>{$ret_text}</span>";
	}
// 変更　ここまで
				$html .= "</label>\n";

				/**
				 *
				 * 各チェックボックスごとにかけるフィルター
				 *
				 */
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i,
					'depth'         => (int) $depth,
					'class'         => $class,
					'attr'          => $attr,
					'text'          => esc_attr( $cfText ),
					'value'         => esc_attr( $metaValues[$i] ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $cf_cnt[$i]->cnt,
					'unit'          => $cfUnit,
					'sep'           => $kugiriFlag,
				);
				$html = apply_filters( 'feas_meta_checkbox_html', $html, $args );

				// ループ前段に結合
				$ret_ele .= $html;

			}

			/**
			 *
			 * チェックボックスグループ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'ret_ele'       => $ret_ele,
				'show_post_cnt' => $showcnt,
			);



// 変更
			// $ret_ele = apply_filters( 'feas_meta_checkbox_group_html', $ret_ele, $args );
//エリアのチェックボックス
if ( $get_data[$cols[2]] == 'meta_cf__fudo_cmn_area' ) {

	$ret_ele = apply_filters( 'feas_meta_checkbox_group_html', $ret_ele, $args );

function create_area_checkbox_html($containerHtml) {
// DOMDocumentを使用してHTMLを解析
$dom = new DOMDocument();
@$dom->loadHTML('<?xml encoding="utf-8" ?>' . $containerHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

$xpath = new DOMXPath($dom);
$labels = $xpath->query("//label");

$data = [
		'parent' => [],
		'child' => [],
		'grandchild' => []
];

// ラベルを分類
foreach ($labels as $label) {
		$input = $xpath->query(".//input", $label)->item(0);
		$span = $xpath->query(".//span", $label)->item(0);

		if ($input && $span) {
				$value = (int)$input->getAttribute('value');
				$text = $span->nodeValue;

				$entry = [
						'value' => $value,
						'text' => $text,
						'element' => $dom->saveHTML($label)
				];

				if ($value <= 99) {
						$data['parent'][] = $entry;
				} elseif ($value >= 100 && $value <= 99999) {
						$data['child'][] = $entry;
				} elseif ($value >= 100000 && $value <= 999999999) {
						$data['grandchild'][] = $entry;
				}
		}
}

// 各カテゴリをソート
foreach ($data as &$items) {
		usort($items, function ($a, $b) {
				return $a['value'] - $b['value'];
		});
}

// 親リストを生成
$ulParent = "<ul class='level_1'>";
foreach ($data['parent'] as $parent) {
		$liParent = "<li>{$parent['element']}";

		// 子要素
		$ulChild = "<ul class='level_2'>";
		foreach ($data['child'] as $child) {
				if (strpos((string)$child['value'], (string)$parent['value']) === 0) {
						$liChild = "<li>{$child['element']}";

						// 孫要素
						$ulGrandchild = "<ul class='level_3'>";
						foreach ($data['grandchild'] as $grandchild) {
								if (strpos((string)$grandchild['value'], (string)$child['value']) === 0) {
										$ulGrandchild .= "<li>{$grandchild['element']}</li>";
								}
						}
						$ulGrandchild .= "</ul>";

						if (strpos($ulGrandchild, "<li>") !== false) {
								$liChild .= $ulGrandchild;
						}

						$liChild .= "</li>";
						$ulChild .= $liChild;
				}
		}
		$ulChild .= "</ul>";

		if (strpos($ulChild, "<li>") !== false) {
				$liParent .= $ulChild;
		}

		$liParent .= "</li>";
		$ulParent .= $liParent;
}
$ulParent .= "</ul>";

// 結果をHTMLに追加
$result = $ulParent;
return $result;
}

$area_checkbox_html = create_area_checkbox_html($ret_ele);

	$ret_ele = '<div class="custom_sort_list">'.$area_checkbox_html.'</div>';

} elseif ( $get_data[$cols[2]] == 'meta_cf__fudo_cmn_traffic' ) {
//交通のチェックボックス

	$ret_ele = apply_filters( 'feas_meta_checkbox_group_html', $ret_ele, $args );

function create_traffic_checkbox_html($containerHtml) {
// DOMDocumentを使用してHTMLを解析
$dom = new DOMDocument();
@$dom->loadHTML('<?xml encoding="utf-8" ?>' . $containerHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

$xpath = new DOMXPath($dom);
$labels = $xpath->query("//label");

$data = [
		'parent' => [],
		'child' => []
];

// ラベルを分類
foreach ($labels as $label) {
		$input = $xpath->query(".//input", $label)->item(0);
		$span = $xpath->query(".//span", $label)->item(0);

		if ($input && $span) {
				$value = (int)$input->getAttribute('value');
				$text = $span->nodeValue;

				$entry = [
						'value' => $value,
						'text' => $text,
						'element' => $dom->saveHTML($label)
				];

				if ($value >= 1000 && $value <= 999999) {
						$data['parent'][] = $entry;
				} elseif ($value >= 1000000 && $value <= 99999999) {
						$data['child'][] = $entry;
				}
		}
}

// 各カテゴリをソート
foreach ($data as &$items) {
		usort($items, function ($a, $b) {
				return $a['value'] - $b['value'];
		});
}

// 親リストを生成
$ulParent = "<ul class='level_1'>";
foreach ($data['parent'] as $parent) {
		$liParent = "<li>{$parent['element']}";

		// 子要素
		$ulChild = "<ul class='level_2'>";
		foreach ($data['child'] as $child) {
				if (strpos((string)$child['value'], (string)$parent['value']) === 0) {
						$liChild = "<li>{$child['element']}";
						$liChild .= "</li>";
						$ulChild .= $liChild;
				}
		}
		$ulChild .= "</ul>";

		if (strpos($ulChild, "<li>") !== false) {
				$liParent .= $ulChild;
		}

		$liParent .= "</li>";
		$ulParent .= $liParent;
}
$ulParent .= "</ul>";

// 結果をHTMLに追加
$result = $ulParent;
return $result;
}

$traffic_checkbox_html = create_traffic_checkbox_html($ret_ele);

	$ret_ele = '<div class="custom_sort_list">'.$traffic_checkbox_html.'</div>';

} else {
	$ret_ele = apply_filters( 'feas_meta_checkbox_group_html', $ret_ele, $args );
}
// 変更　ここまで


			break;

		/**
		 *	ラジオボタン
		 */
		case '4':
		case 'd':

			/**
			 *	ラジオボタンの「未選択」の表示/非表示
			 */
			$noselect_status = get_option( $cols[31] . $manag_no . '_' . $number );
			if ( $noselect_status ) {

				$ret_ele .= "<label for='feas_" . esc_attr( $manag_no . "_" . $number ) . "_none' class='feas_clevel_01'>";
				$ret_ele .= "<input id='feas_" . esc_attr( $manag_no . "_" . $number ) . "_none' type='radio' name='search_element_" . esc_attr( $number ) . "' value='' />";
				$ret_ele .= "<span>" . esc_html( $noselect_text ) . "</span>";
				$ret_ele .= "</label>\n";
			}

			for ( $i = 0; $i < $cntValues; $i++ ) {

				// 0件のカスタムフィールドは表示しない場合
				if ( $nocnt && $cf_cnt[$i]->cnt == 0 )
					continue;

				// 真の選択肢を表示or非表示
				if ( '1' === $cf_scf && '1' === $metaValues[$i] && '1' !== $show_shingi )
					continue;

				// 偽の選択肢を表示or非表示
				if ( '1' === $cf_scf && '0' === $metaValues[$i] && '1' !== $show_shingi_alt )
					continue;


				$checked = '';
				if ( isset( $_GET['fe_form_no'] ) && $_GET['fe_form_no'] == $manag_no ) {
					if ( isset( $_GET['search_element_' . $number] ) ) {
						$searchTerm = stripslashes( $_GET['search_element_' . $number] );

// 変更
						// if ( $searchTerm == $metaValues[$i] ) {
						// 	$checked = ' checked';
						// }

//築年月のchecked判定を変更
if ( $get_data[$cols[2]] == 'meta_cf__fudo_cmn_chikunengetsu' ) {
	$chikunengetsu_val  = esc_attr( $metaValues[$i] );
	$chikunengetsu_val  = $chikunengetsu_val + $current_year_month - 1000;

	if ( $searchTerm == $chikunengetsu_val ) {
		$checked = ' checked';
	}
} else {
	if ( $searchTerm == $metaValues[$i] ) {
		$checked = ' checked';
	}
}
// 変更　ここまで

					}
				} elseif ( $default_value ) {
					if ( is_array( $default_value ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( $default_value[$i_lists] == $metaValues[$i] ) {
								$checked = ' checked';
							}
						}
					}
				}

				// 要素内の並び順 = カスタム「以外」
				if ( 'b' != $get_data[$cols[5]] ) {

					// 真偽値の場合
					if ( '1' === $cf_scf ) {

						// 真の場合の文字列
						//$cfText = get_option( $cols[25] . $manag_no . "_" . $number );

						if ( '1' === $metaValues[$i] ) {
							// 真の場合の文字列
							$cfText = get_option( $cols[25] . $manag_no . "_" . $number );
						} else {
							// 偽の場合の文字列
							$cfText = get_option( $cols[40] . $manag_no . "_" . $number );
						}

					// 「関連する投稿」
					} elseif ( '2' === $cf_scf ) {

						$relatedPost = get_post( $metaValues[$i] );
						$cfText = $relatedPost->post_title;

					// 「関連するターム」
					} elseif ( '3' === $cf_scf ) {

						$relatedTerm = get_term( $metaValues[$i] );
						$cfText = $relatedTerm->name;

					} else {

						if ( 'yes' == $kugiriFlag && is_numeric( $metaValues[$i] ) ) {
							$cfText = number_format( $metaValues[$i], 0, '.', ',' );
						} else {
							$cfText = $metaLabels[$i];
						}
					}

					if ( '0' === $get_data[$cols[26]] ) {
						$cfText = $cfUnit . $cfText; // 単位が前
					} else {
						$cfText = $cfText . $cfUnit; // 単位が後
					}

				} else {
					$cfText = $savedValues[$i]['text'];
				}

				if ( 'yes' == $showcnt ) {
					$text = $cfText . " ({$cf_cnt[$i]->cnt}) ";
				} else {
					$text = $cfText;
				}

				$depth = '01';

				// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassを準備
				if ( 'b' === $get_data[$cols[5]] ) {
					$depth = str_pad( $savedValues[$i]['depth'], 2, '0', STR_PAD_LEFT );
				}

				/**
				 *
				 * チェックボックスのclassにかけるフィルター
				 *
				 */
				$class = 'feas_clevel_' . esc_attr( $depth );
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i,
					'text'          => esc_attr( $cfText ),
					'value'         => esc_attr( $metaValues[$i] ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $cf_cnt[$i]->cnt,
				);
				$class = apply_filters( 'feas_meta_radio_class', $class, $args );

				/**
				 *
				 * 各ラジオボタンのattrにかけるフィルター
				 *
				 */
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i,
					'class'         => $class,
					'text'          => esc_attr( $cfText ),
					'value'         => esc_attr( $metaValues[$i] ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $cf_cnt[$i]->cnt,
				);
				$attr = apply_filters( 'feas_meta_radio_attr', $attr, $args );

				$ret_id   = esc_attr( "feas_{$manag_no}_{$number}_{$i}" );
				$ret_name = esc_attr( "search_element_{$number}" );


// 変更
//				$ret_val  = esc_attr( $metaValues[$i] );
// 築年月のvalueの値を変更
			if ( $get_data[$cols[2]] == 'meta_cf__fudo_cmn_chikunengetsu' ) {
				$ret_val  = esc_attr( $metaValues[$i] );
				$ret_val  = $ret_val + $current_year_month - 1000;
			} else {
				$ret_val  = esc_attr( $metaValues[$i] );
			}
// 変更 ここまで


				$rel_txt  = esc_html( $text );

				$html  = "<label for='{$ret_id}' class='{$class}'>";
				$html .= "<input type='radio' id='{$ret_id}' name='{$ret_name}' value='{$ret_val}' {$attr} {$checked} />";
				$html .= "<span>{$rel_txt}</span>";
				$html .= "</label>\n";

				/**
				 *
				 * 各ラジオボタンごとにかけるフィルター
				 *
				 */
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i,
					'depth'         => (int) $depth,
					'class'         => $class,
					'attr'          => $attr,
					'text'          => esc_attr( $cfText ),
					'value'         => esc_attr( $metaValues[$i] ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $cf_cnt[$i]->cnt,
					'unit'          => $cfUnit,
					'sep'           => $kugiriFlag,
				);
				$html = apply_filters( 'feas_meta_radio_html', $html, $args );

				// ループ前段に結合
				$ret_ele .= $html;

			}

			/**
			 *
			 * ラジオボタングループ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'ret_ele'       => $ret_ele,
				'show_post_cnt' => $showcnt,
			);
			$ret_ele = apply_filters( 'feas_meta_radio_group_html', $ret_ele, $args );

			break;

		/**
		 *	フリーワード
		 */
		case '5':
		case 'e':

			$placeholder_data = '';
			$placeholder = '';
			$output_js = '';

			$placeholder_data = $get_data[$cols[30]];
			if ( $placeholder_data ) {
				$placeholder = ' placeholder="' . esc_attr( $placeholder_data ) . '"';
				$output_js = '<script>jQuery("#feas_' . esc_attr( $manag_no . '_' . $number ) . '").focus(function(){jQuery(this).attr("placeholder",""); }).blur(function(){
    jQuery(this).attr("placeholder", "' . esc_attr( $placeholder_data ) . '");});</script>';
			}

			$s_keyword = '';
			if ( isset( $_GET['fe_form_no'] ) && $manag_no == $_GET['fe_form_no'] ) {
				if ( isset( $_GET['s_keyword_' . $number] ) ) {
					$s_keyword = $_GET['s_keyword_' . $number];
				}
			} elseif ( $default_value ) {
				if ( is_array( $default_value ) ) {
					for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
						if ( '' !== $s_keyword ) {
							$s_keyword .= ' ';
						}
						$s_keyword .= $default_value[$i_lists];
					}
				}
			}

			/**
			 *
			 * inputのclassにかけるフィルター
			 *
			 */
			$class = 'feas_meta_freeword';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$class = apply_filters( 'feas_meta_freeword_class', $class, $args );

			/**
			 *
			 * inputのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$attr = apply_filters( 'feas_meta_freeword_attr', $attr, $args );

			// Sanitize
			$ret_id   = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_name = esc_attr( "s_keyword_{$number}" );
			$ret_val  = esc_attr( stripslashes( $s_keyword ) );

			$html  = "<input type='text' name='{$ret_name}' id='{$ret_id}' class='{$class}' value='{$ret_val}' {$placeholder} {$attr} />";
			$html .= $output_js;

			/**
			 *
			 * AND/ORオプション
			 *
			 */
			$andor_html = '';
			$andor_ui_flag = $get_data[$cols[6]];

			if ( 'c' === $andor_ui_flag ) {

				// Sanitize
				$ret_6_id    = esc_attr( "feas_{$manag_no}_{$number}_andor" );
				$ret_6_name  = esc_attr( "feas_andor_{$number}" );

				/**
				 * Filter for class
				 */
				$ret_6_class = 'feas_freeword_andor';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_6_class = esc_attr( apply_filters( 'feas_freeword_andor_class', $ret_6_class, $args ) );

				/**
				 * Filter apply to the text "Exclude"
				 */
				$ret_6_or_text = 'OR';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_6_or_text  = esc_html( apply_filters( 'feas_freeword_andor_or_text', $ret_6_or_text, $args ) );

				$ret_6_and_text = 'AND';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_6_and_text = esc_html( apply_filters( 'feas_freeword_andor_and_text', $ret_6_and_text, $args ) );

				$checked_0 = $checked_1 = '';
				if ( isset( $_GET["{$ret_6_name}"] ) && 'a' === $_GET["{$ret_6_name}"] ) {
					$checked_0 = 'checked';
				} else {
					$checked_1 = 'checked';
				}

				$andor_html  = "<label for='{$ret_6_id}_0' class='{$ret_6_class}'>";
				$andor_html .= "<input type='radio' id='{$ret_6_id}_0' name='{$ret_6_name}' value='a' {$checked_0} />";
				$andor_html .= $ret_6_or_text;
				$andor_html .= "</label>";
				$andor_html .= "<label for='{$ret_6_id}_1' class='{$ret_6_class}'>";
				$andor_html .= "<input type='radio' id='{$ret_6_id}_1' name='{$ret_6_name}' value='b' {$checked_1} />";
				$andor_html .= $ret_6_and_text;
				$andor_html .= "</label>";
			}

			/**
			 *
			 * 除外オプション
			 *
			 */
			$exclude_html = '';
			$exclude_ui_flag = $get_data[$cols[52]];

			if ( '2' === $exclude_ui_flag ) {

				// Sanitize
				$ret_52_id    = esc_attr( "feas_{$manag_no}_{$number}_exclude" );
				$ret_52_name  = esc_attr( "feas_exclude_{$number}" );

				/**
				 * Filter for class
				 */
				$ret_52_class = 'feas_freeword_exclude';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_52_class = esc_attr( apply_filters( 'feas_freeword_exclude_class', $ret_52_class, $args ) );

				/**
				 * Filter apply to the text "Exclude"
				 */
				$ret_52_text = '除外';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_52_text = esc_html( apply_filters( 'feas_freeword_exclude_text', $ret_52_text, $args ) );

				$checked = '';
				if ( isset( $_GET["{$ret_52_name}"] ) && '1' === $_GET["{$ret_52_name}"] ) {
					$checked = 'checked';
				}

				$exclude_html  = "<label for='{$ret_52_id}' class='{$ret_52_class}'>";
				$exclude_html .= "<input type='checkbox' id='{$ret_52_id}' name='{$ret_52_name}' value='1' {$checked} />";
				$exclude_html .= $ret_52_text;
				$exclude_html .= "</label>";
			}

			/**
			 *
			 * 完全一致オプション
			 *
			 */
			$exact_html = '';
			$exact_ui_flag = $get_data[$cols[53]];
			if ( '2' === $exact_ui_flag ) {

				// Sanitize
				$ret_53_id    = esc_attr( "feas_{$manag_no}_{$number}_exact" );
				$ret_53_name  = esc_attr( "feas_exact_{$number}" );

				/*
				 * Filter for class
				 */
				$ret_53_class = 'feas_freeword_exact';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_53_class = esc_attr( apply_filters( 'feas_freeword_exact_class', $ret_53_class, $args ) );

				/*
				 * Filter apply to the text "Exclude"
				 */
				$ret_53_text = '完全一致';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_53_text = esc_html( apply_filters( 'feas_freeword_exact_text', $ret_53_text, $args ) );

				$checked = '';
				if ( isset( $_GET["{$ret_53_name}"] ) && '1' === $_GET["{$ret_53_name}"] ) {
					$checked = 'checked';
				}

				$exact_html  = "<label for='{$ret_53_id}' class='{$ret_53_class}'>";
				$exact_html .= "<input type='checkbox' id='{$ret_53_id}' name='{$ret_53_name}' value='1' {$checked} />";
				$exact_html .= $ret_53_text;
				$exact_html .= "</label>";
			}

			if ( 'c' === $andor_ui_flag || '2' === $exclude_ui_flag || '2' === $exact_ui_flag ) {

				$tmp_html  = '<div class="feas_inline_group">';
				$tmp_html .= $html;
				$tmp_html .= '<div class="feas_wrap_options">';
				$tmp_html .= $andor_html . $exclude_html . $exact_html;
				$tmp_html .= '</div>';
				$tmp_html .= "</div>";

				$html = $tmp_html;
			}

			if ( '' !== $get_data[$cols[20]] ) {
				$html .= create_specifies_the_key_element( $get_data, $number );
			}

			/**
			 *
			 * inputタグ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'attr'     => $attr,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$html = apply_filters( 'feas_meta_freeword_group_html', $html, $args );

			$ret_ele .= $html;

			break;

		/**
		 *	グループ
		 */
		case 'f':

			break;

		/**
		 *	テキスト入力で範囲検索
		 */
		case 'cf_limit_keyword':

			$s_keyword = '';
			if ( isset( $_GET['fe_form_no'] ) && $manag_no == $_GET['fe_form_no'] ) {
				if ( isset( $_GET['cf_limit_keyword_' . $number] ) ) {
					$s_keyword = $_GET['cf_limit_keyword_' . $number];
				}
			}

			/**
			 *
			 * inputのclassにかけるフィルター
			 *
			 */
			$class = 'feas_meta_range';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$class = apply_filters( 'feas_meta_range_class', $class, $args );

			/**
			 *
			 * inputのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$attr = apply_filters( 'feas_meta_range_attr', $attr, $args );

			// Sanitize
			$ret_id   = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_name = esc_attr( "cf_limit_keyword_{$number}" );
			$ret_val  = esc_attr( stripslashes( $s_keyword ) );

			$html = "<input type='text' name='{$ret_name}' id='{$ret_id}' value='{$ret_val}' {$attr} />";

			/**
			 *
			 * inputタグ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'attr'     => $attr,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$html = apply_filters( 'feas_meta_range_group_html', $html, $args );

			$ret_ele .= $html;

			break;

		/**
		 *	その他
		 */
		default:

			$s_keyword = '';
			if ( isset( $_GET['fe_form_no'] ) && $manag_no == $_GET['fe_form_no'] ) {
				if ( isset( $_GET['s_keyword_' . $number] ) ) {
					$s_keyword = $_GET['s_keyword_' . $number];
				}
			}

			/**
			 *
			 * inputのclassにかけるフィルター
			 *
			 */
			$class = 'feas_meta_freeword';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$class = apply_filters( 'feas_meta_freeword_class', $class, $args );

			/**
			 *
			 * inputのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$attr = apply_filters( 'feas_meta_freeword_attr', $attr, $args );

			// Sanitize
			$ret_id   = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_name = esc_attr( "s_keyword_{$number}" );
			$ret_val  = esc_attr( stripslashes( $s_keyword ) );

			$html  = "<input type='text' name='{$ret_name}' id='{$ret_id}' class='{$class}' value='{$ret_val}' {$placeholder} {$attr} />";

			/**
			 *
			 * inputタグ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'attr'     => $attr,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$html = apply_filters( 'feas_meta_freeword_group_html', $html, $args );

			$ret_ele .= $html;

			break;
	}

	return $ret_ele;
}


/*============================
	タグ（tag）のエレメント作成
 ============================*/
function create_tag_element( $get_data, $number ) {

	global $wpdb, $cols, $feadvns_show_count, $manag_no, $feadvns_include_sticky, $feadvns_search_target, $feadvns_exclude_id, $feadvns_default_cat, $feadvns_default_page, $feadvns_exclude_term_id;

	$nocnt = false;
	$exclude_post_ids = $default_page = '';
	$sql = $excat = $exids = $exid = $target_pt = $target_sp = $showcnt = $ret_ele = $order_by = $taxonomy = $lang = $polylang_sql = $wpml_sql = null;
	$excat_array = $sticky = $q_term_id = $sp = $get_cats = $get_tags = array();

	// 検索対象のpost_typeを取得
	$target_pt_tmp = get_option( $feadvns_search_target . $manag_no );
	if ( $target_pt_tmp ) {
		$target_pt = "'" . implode( "','", (array) $target_pt_tmp ) . "'";
	} else {
		$target_pt = "'post'";
	}

	// 固定記事(Sticky Posts)を検索対象から省く場合、カウントに含めない
	$target_sp = get_option( $feadvns_include_sticky . $manag_no );
	if ( 'yes' != $target_sp ) {
		$sticky = get_option( 'sticky_posts' );
		if ( ! empty( $sticky ) ) {
			$sp = array_merge( $sp, $sticky ); // 除外IDにマージ
		}
	}

	// 投稿ステータス
	if ( in_array( 'attachment', (array) $target_pt_tmp ) ) {
		$post_status = "'publish', 'inherit'";
	} else {
		$post_status = "'publish'";
	}

	// 固定条件 > タクソノミ／ターム
	$fixed_term = get_option( $feadvns_default_cat . $manag_no );

	// 固定条件 > 親ページ
	$default_page = get_option( $feadvns_default_page . $manag_no );
	if ( $default_page ) {
		$default_page = implode( ',', (array) $default_page );
		$default_page = " AND p.post_parent IN (" . esc_sql( $default_page ) . ")";
	}

	// 検索条件に件数を表示
	$showcnt = get_option( $feadvns_show_count . $manag_no );

	// 除外する記事ID
	$exclude_id = get_option( $feadvns_exclude_id . $manag_no );
	if ( $exclude_id ) {
		$sp = array_merge( $sp, $exclude_id ); // 除外IDにマージ
	}

	// 検索結果から除外するタームID（全体）
	// タームごとのカウントに反映するため
	$exclude_term_id = get_option( $feadvns_exclude_term_id . $manag_no );
	if ( $exclude_term_id ) {
		$args['cat']      = $exclude_term_id;
		$args['format']   = 'array';
		$args['mode']     = 'exclude';
		$dcat['orderby']  = '';
		$exclude_post_ids = create_where_single_cat( $args );
	}

	// 除外タームのSQLを構成
	if ( $exclude_post_ids ) {
		$sp = array_merge( $sp, $exclude_post_ids ); // 除外IDにマージ
	}

	// 除外IDをカンマ区切りにする
	if ( $sp ) {
		$sp = implode( ',', $sp );
	}

	// 除外タームID（個別）が設定されている場合
	if ( isset( $get_data[$cols[11]] ) && $get_data[$cols[11]] != '' ) {
		$exids = explode( ',', $get_data[$cols[11]] );
	}

	// 0件のタームを表示しない設定の場合
	if ( isset( $get_data[$cols[14]] ) && $get_data[$cols[14]] == 'no' ) {
		$nocnt = true;
	}

	// 条件内の並び順
	$order_by = " t.term_id ASC ";

	if ( isset( $get_data[$cols[5]] ) ) {

		switch ( (string) $get_data[$cols[5]] ) {

			case '0':
			case '1':
			case 'c':
				$order_by = " t.term_id ";
				break;
			case '2':
			case '3':
			case 'd':
				$order_by = " t.name ";
				break;
			case '4':
			case '5':
			case 'e':
				$order_by = " t.slug ";
				break;
			case '6':
			case 'f':
				$order_by = " t.term_order ";
				break;
			case '7':
			case 'g':
				$order_by = " RAND() ";
				break;
			default:
				$order_by = " t.term_id ";
				break;
		}
		// 'b'（自由記述）については2046行目〜にて
	}

	/**
	 *	条件内の並び順 昇順/降順
	 */
	$order = " ASC";

	if ( isset( $get_data[$cols[35]] ) ) {
		switch ( $get_data[$cols[35]] ) {

			case 'asc':
				$order = " ASC";
				break;
			case 'desc':
				$order = " DESC";
				break;
			default:
				$order = " ASC";
				break;
		}
	}

	// 「要素内の並び順」が「自由記述」の場合は、ターム一覧をDBから呼び出す代わりに記述内容で配列get_catsを構成
	if ( 'b' === $get_data[$cols[5]] ) {

		$options = $get_data[$cols[36]];

		if ( ! empty( $options ) ) {

			$get_tags = array();

			// 行数分ループを回す
			for ( $i = 0; $cnt = count( $options ), $i < $cnt; $i++ ) {

				if ( empty( $options[$i] ) )
					continue;

				$get_tags[$i] = new stdClass();

				// 値
				$get_tags[$i]->term_id = $options[$i]['value'];

				// 表記
				$get_tags[$i]->name = $options[$i]['text'];

				// 階層
				$get_tags[$i]->depth = $options[$i]['depth'];
			}
		}
	}
	else {

		// Polylang
		$lang = $polylang_join_sql = $polylang_sql = NULL;

		if ( in_array( 'polylang/polylang.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

			$polylang = get_option( 'polylang' );
			if ( $polylang ) {
				$lang = $polylang['default_lang'];
			}

			$langVar = get_query_var( 'lang' );
			if ( $langVar ) {
				$lang = $langVar;
			}

			if ( $lang ) {

				$polylang_join_sql = " INNER JOIN {$wpdb->term_relationships} AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id ";

				$sql = <<<SQL
SELECT tt.term_id
FROM {$wpdb->term_taxonomy} AS tt
LEFT JOIN {$wpdb->terms} AS t
ON tt.term_id = t.term_id
WHERE t.slug = %s
LIMIT 1
SQL;
				$sql = $wpdb->prepare( $sql, 'pll_' . $lang );
				$lang_id = $wpdb->get_var( $sql );
				if ( $lang_id ) {
					$addSql = "AND tr.term_taxonomy_id = %d ";
					$polylang_sql = $wpdb->prepare( $addSql, $lang_id );
				}
			}
		}

		// WPML
		$wpml_join_sql = $wpml_where_sql = NULL;
		if ( in_array( 'sitepress-multilingual-cms/sitepress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			$wpml_lang = apply_filters( 'wpml_current_language', NULL );
			if ( $wpml_lang ) {
				$wpml_join_sql  = " LEFT JOIN {$wpdb->prefix}icl_translations AS wpml_translations ON t.term_id = wpml_translations.element_id ";
				$sql            = " AND wpml_translations.element_type = 'tax_post_tag' ";
				$sql           .= " AND wpml_translations.language_code = %s ";
				$wpml_where_sql = $wpdb->prepare( $sql, $wpml_lang );
			}
		}

		/*
		 * Cache first.
		 * Retrive from DB if the cache doesn't exist.
		 */
		if ( false === ( $get_tags = feas_cache_judgment( $manag_no, 'tag', $number ) ) ) {
			$sql  = " SELECT t.name, t.term_id ";
			$sql .= " FROM {$wpdb->terms} AS t ";
			$sql .= " INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_id = t.term_id ";
			if ( $polylang_join_sql ) {
				$sql .= $polylang_join_sql;
			}
			if ( $wpml_join_sql ) {
				$sql .= $wpml_join_sql;
			}
			$sql .= " WHERE tt.taxonomy = 'post_tag' ";
			if ( $exids ) {
				$exids_placeholders = implode( ', ', array_fill( 0, count( $exids ), '%d' ) );
				$addSql = " AND t.term_id NOT IN ( {$exids_placeholders} ) ";
				$sql .= $wpdb->prepare( $addSql, $exids );
			}
			if ( $polylang_sql ) {
				$sql .= $polylang_sql;
			}
			if ( $wpml_where_sql ) {
				$sql .= $wpml_where_sql;
			}
			$sql .= " GROUP BY t.term_id ";
			$sql .= " ORDER BY {$order_by} {$order} ";
			$get_tags = $wpdb->get_results( $sql );

			feas_cache_create( $manag_no, 'tag', $number, $get_tags );
		}
	}

	$cnt_ele = count( $get_tags );

	/**
	 *	件数を取得してキャッシュ保存
	 */
	if ( $get_tags ) {

		$tag_cnt = array();
		foreach( $get_tags as $term_id ) {

			if ( false === ( $cnt = feas_cache_judgment( $manag_no, 'tag_cnt_' . $term_id->term_id, false ) ) ) {
				$sql  = " SELECT count( p.ID ) AS cnt FROM {$wpdb->posts} AS p";
				$sql .= " INNER JOIN {$wpdb->term_relationships} AS tr ON p.ID = tr.object_id";
				if ( $fixed_term ) $sql .= " INNER JOIN {$wpdb->term_relationships} AS tr2 ON p.ID = tr2.object_id";
				$sql .= " WHERE 1=1";
				if ( $sp ) $sql .= " AND p.ID NOT IN ( {$sp} )";
				if ( $default_page ) {
					$sql .= $default_page;
				}
				$sql .= " AND tr.term_taxonomy_id = " . esc_sql( $term_id->term_id );
				if ( $fixed_term ) $sql .= " AND tr2.term_taxonomy_id = " . esc_sql( $fixed_term );
				$sql .= " AND p.post_type IN( {$target_pt} )";
				$sql .= " AND p.post_status IN ( {$post_status} )";

				$cnt = $wpdb->get_row( $sql );
				feas_cache_create( $manag_no, 'tag_cnt_' . $term_id->term_id, false, $cnt );
			}
			$tag_cnt[] = $cnt;
		}
	}

	/**
	 *	未選択時の文字列
	 */
	$noselect_text = $get_data[$cols[27]];

	/**
	 *	デフォルト値
	 */
	$default_value = $get_data[$cols[39]];
	if ( '' !== $default_value ) {
		$default_value = explode( ',', $default_value );
	}

	switch ( (string) $get_data[$cols[4]] ) {

		/**
		 *	ドロップダウン
		 */
		case '1':
		case 'a':

			$ret_opt = '';

			for ( $i_ele = 0; $i_ele < $cnt_ele; $i_ele++) {

				// 0件のタグは表示しない場合
				if ( $nocnt && $tag_cnt[$i_ele]->cnt == 0 )
					continue;

				$selected = '';
				if ( isset( $_GET['fe_form_no'] ) && $_GET['fe_form_no'] == $manag_no ) {
					if ( isset( $_GET['search_element_' . $number] ) ) {
						if ( $_GET['search_element_' . $number] == $get_tags[$i_ele]->term_id ) {
							$selected = ' selected';
						}
					}
				} elseif ( $default_value ) {
					if ( is_array( $default_value ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( $default_value[$i_lists] == $get_tags[$i_ele]->term_id ) {
								$selected = ' selected';
							}
						}
					}
				}

				$cat_cnt = '';
				if ( 'yes' == $showcnt ) {
					$cat_cnt = " (" . $tag_cnt[$i_ele]->cnt . ") ";
				}

				$depth = '01';
				$indentSpace = '';

				// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassとインデントを準備
				if ( 'b' === $get_data[$cols[5]] ) {
					if ( '1' !== $get_tags[$i_ele]->depth ) {
						$depth = str_pad( $get_tags[$i_ele]->depth, 2, '0', STR_PAD_LEFT );
						for ( $i_depth = 1; $i_depth < $get_tags[$i_ele]->depth; $i_depth++ ) {
							$indentSpace .= '&nbsp;&nbsp;';
						}
					}
				}

				/**
				 *
				 * optionのclassにかけるフィルター
				 *
				 */
				$class = 'feas_clevel_' . esc_attr( $depth );
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'parent'        => 0,
					'depth'         => 1,
					'text'          => esc_attr( $get_tags[$i_ele]->name ),
					'value'         => esc_attr( $get_tags[$i_ele]->term_id ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $tag_cnt[$i_ele]->cnt,
				);
				$class = apply_filters( 'feas_tag_dropdown_class', $class, $args );

				/**
				 *
				 * optionのattrにかけるフィルター
				 *
				 */
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'parent'        => 0,
					'depth'         => 1,
					'class'         => $class,
					'text'          => esc_attr( $get_tags[$i_ele]->name ),
					'value'         => esc_attr( $get_tags[$i_ele]->term_id ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $tag_cnt[$i_ele]->cnt,
				);
				$attr = apply_filters( 'feas_tag_dropdown_attr', $attr, $args );

				// Sanitaize
				$ret_id   = esc_attr( "feas_{$manag_no}_{$number}_{$i_ele}" );
				$ret_val  = esc_attr( $get_tags[$i_ele]->term_id );
				$rel_text = esc_html( $get_tags[$i_ele]->name . $cat_cnt );

				$html  = "<option id='{$ret_id}' value='{$ret_val}' class='{$class}' $selected>";
				$html .= $indentSpace . $rel_text;
				$html .= "</option>\n";

				/**
				 *
				 * 各optionごとにかけるフィルター
				 *
				 */
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'parent'        => 0,
					'depth'         => 1,
					'class'         => $class,
					'attr'          => $attr,
					'text'          => esc_attr( $get_tags[$i_ele]->name ),
					'value'         => esc_attr( $get_tags[$i_ele]->term_id ),
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $tag_cnt[$i_ele]->cnt,
				);
				$html = apply_filters( 'feas_tag_dropdown_html', $html, $args );

				$ret_opt .= $html;
			}

			/**
			 *
			 * selectのclassにかけるフィルター
			 *
			 */
			$class = 'feas_tag_dropdown';
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'ret_opt'       => $ret_opt,
				'show_post_cnt' => $showcnt,
			);
			$class = apply_filters( 'feas_tag_dropdown_group_class', $class, $args );

			/**
			 *
			 * selectのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'class'         => $class,
				'ret_opt'       => $ret_opt,
				'show_post_cnt' => $showcnt,
			);
			$attr = apply_filters( 'feas_tag_dropdown_group_attr', $attr, $args );

			// Sanitize
			$ret_name = esc_attr( "search_element_{$number}" );
			$ret_id   = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_txt  = esc_html( $noselect_text );

			$ret_ele .= "<select name='{$ret_name}' id='{$ret_id}' class='{$class}' {$attr}>\n";
			$ret_ele .= "<option id='{$ret_id}_none' value=''>\n";
			$ret_ele .= $ret_txt;
			$ret_ele .= "</option>\n";
			$ret_ele .= $ret_opt;
			$ret_ele .= "</select>\n";

			/**
			 *
			 * select全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'parent'        => 0,
				'depth'         => 1,
				'class'         => $class,
				'attr'          => $attr,
				'show_post_cnt' => $showcnt,
				'ret_opt'       => $ret_opt,
			);
			$ret_ele = apply_filters( 'feas_tag_dropdown_group_html', $ret_ele, $args );

			break;

		/**
		 *	セレクトボックス
		 */
		case '2':
		case 'b':

			$ret_opt = '';
			$selected_cnt = 0;

			for ( $i_ele = 0; $i_ele < $cnt_ele; $i_ele++ ) {

				// 0件のタグは表示しない場合
				if ( $nocnt && $tag_cnt[$i_ele]->cnt == 0 )
					continue;

				$selected = '';
				if ( isset( $_GET['fe_form_no'] ) && $_GET['fe_form_no'] == $manag_no ) {
					if ( isset( $_GET["search_element_" . $number] ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $_GET["search_element_" . $number] ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( isset( $_GET["search_element_" . $number][$i_lists] ) ) {
								if ( $_GET["search_element_" . $number][$i_lists] == $get_tags[$i_ele]->term_id ) {
									$selected = ' selected';
								}
							}
						}
					}
				} elseif ( $default_value ) {
					if ( is_array( $default_value ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( $default_value[$i_lists] == $get_tags[$i_ele]->term_id ) {
								$selected = ' selected';
							}
						}
					}
				}

				$cat_cnt = '';
				if ( 'yes' == $showcnt ) {
					$cat_cnt = " (" . $tag_cnt[$i_ele]->cnt . ") ";
				}

				$depth = '01';
				$indentSpace = '';

				// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassとインデントを準備
				if ( 'b' === $get_data[$cols[5]] ) {
					if ( '1' !== $get_tags[$i_ele]->depth ) {
						$depth = str_pad( $get_tags[$i_ele]->depth, 2, '0', STR_PAD_LEFT );
						$indentSpace = '';
						for ( $i_depth = 1; $i_depth < $get_tags[$i_ele]->depth; $i_depth++ ) {
							$indentSpace .= '&nbsp;&nbsp;';
						}
					}
				}

				/**
				 *
				 * optionのclassにかけるフィルター
				 *
				 */
				$class = 'feas_clevel_' . esc_attr( $depth );
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'parent'        => 0,
					'depth'         => 1,
					'text'          => esc_attr( $get_tags[$i_ele]->name ),
					'value'         => esc_attr( $get_tags[$i_ele]->term_id ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $tag_cnt[$i_ele]->cnt,
				);
				$class = apply_filters( 'feas_tag_multiple_class', $class, $args );

				/**
				 *
				 * optionのattrにかけるフィルター
				 *
				 */
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'parent'        => 0,
					'depth'         => 1,
					'class'         => $class,
					'text'          => esc_attr( $get_tags[$i_ele]->name ),
					'value'         => esc_attr( $get_tags[$i_ele]->term_id ),
					'selected'      => $selected,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $tag_cnt[$i_ele]->cnt,
				);
				$attr = apply_filters( 'feas_tag_multiple_attr', $attr, $args );

				// Sanitize
				$ret_id   = esc_attr( "feas_{$manag_no}_{$number}_{$i_ele}" );
				$ret_val  = esc_attr( $get_tags[$i_ele]->term_id );
				$ret_text = esc_html( $get_tags[$i_ele]->name . $cat_cnt );

				$html  = "<option id='{$ret_id}' value='{$ret_val}' class='{$class}' $selected>";
				$html .= $indentSpace . $ret_text;
				$html .= "</option>\n";

				/**
				 *
				 * 各オプションごとにかけるフィルター
				 *
				 */
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'parent'        => 0,
					'depth'         => 1,
					'class'         => $class,
					'attr'          => $attr,
					'text'          => esc_attr( $get_tags[$i_ele]->name ),
					'value'         => esc_attr( $get_tags[$i_ele]->term_id ),
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $tag_cnt[$i_ele]->cnt,
				);
				$html = apply_filters( 'feas_tag_multiple_html', $html, $args );

				// ループ前段に結合
				$ret_opt .= $html;
			}

			// iOSではセレクトボックスが1行にまとめられてしまい、selectedが1件も付いていないと「0項目」と表示されてしまい、未選択時テキストが表示されないため。
			$selected = '';
			if ( 0 === $selected_cnt ) {
				if ( wp_is_mobile() ) {
					$selected = ' selected';
				}
			}

			// Sanitize
			$ret_name = esc_attr( "search_element_{$number}[]" );
			$ret_id   = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_txt  = esc_html( $noselect_text );

			$html  = "<select name='{$ret_name}' id='{$ret_id}' size='5' multiple='multiple'>\n";
			$html .= "<option id='{$ret_id}_none' value='' {$selected}>";
			$html .= $ret_txt;
			$html .= "</option>\n";
			$html .= $ret_opt;
			$html .= "</select>\n";

			/**
			 *
			 * セレクトボックス全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'parent'        => 0,
				'depth'         => 1,
				'class'         => $class,
				'attr'          => $attr,
				'show_post_cnt' => $showcnt,
				'ret_opt'       => $ret_opt,
			);
			$html = apply_filters( 'feas_tag_multiple_group_html', $html, $args );

			// ループ前段に結合
			$ret_ele .= $html;

			break;

		/**
		 *	チェックボックス
		 */
		case '3':
		case 'c':

			//$ret_ele = "<div class='feas-item-content'>";
			for ( $i_ele = 0; $i_ele < $cnt_ele; $i_ele++ ) {

				// 0件のタグは表示しない場合
				if ( $nocnt && $tag_cnt[$i_ele]->cnt == 0 )
					continue;

				$checked = '';
				if ( isset( $_GET['fe_form_no'] ) && $_GET['fe_form_no'] == $manag_no ) {
					if ( isset( $_GET["search_element_" . $number] ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $_GET["search_element_" . $number] ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( isset( $_GET["search_element_" . $number][$i_lists] ) ) {
								if ( $_GET["search_element_" . $number][$i_lists] == $get_tags[$i_ele]->term_id ) {
									$checked = ' checked';
								}
							}
						}
					}
				} elseif ( $default_value ) {
					if ( is_array( $default_value ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( $default_value[$i_lists] == $get_tags[$i_ele]->term_id ) {
								$checked = ' checked';
							}
						}
					}
				}

				$cat_cnt = '';
				if ( 'yes' == $showcnt ) {
					$cat_cnt = " (" . $tag_cnt[$i_ele]->cnt . ") ";
				}

				$depth = '01';

				// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassを準備
				if ( 'b' === $get_data[$cols[5]] ) {
					$depth = str_pad( $get_tags[$i_ele]->depth, 2, '0', STR_PAD_LEFT );
				}

				/**
				 *
				 * checkboxのclassにかけるフィルター
				 *
				 */
				$class = 'feas_clevel_' . esc_attr( $depth );
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'text'          => esc_attr( $get_tags[$i_ele]->name ),
					'value'         => esc_attr( $get_tags[$i_ele]->term_id ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $tag_cnt[$i_ele]->cnt,
				);
				$class = apply_filters( 'feas_tag_checkbox_class', $class, $args );

				/**
				 *
				 * checkboxのattrにかけるフィルター
				 *
				 */
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'class'         => $class,
					'text'          => esc_attr( $get_tags[$i_ele]->name ),
					'value'         => esc_attr( $get_tags[$i_ele]->term_id ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $tag_cnt[$i_ele]->cnt,
				);
				$attr = apply_filters( 'feas_tag_checkbox_attr', $attr, $args );

				// Sanitize
				$ret_id   = esc_attr( "feas_{$manag_no}_{$number}_{$i_ele}" );
				$ret_name = esc_attr( "search_element_{$number}[]" );
				$ret_val  = esc_attr( $get_tags[$i_ele]->term_id );
				$ret_text = esc_html( $get_tags[$i_ele]->name . $cat_cnt );

				$html  = "<label for='{$ret_id}' class='{$class}'>";
				$html .= "<input id='{$ret_id}' type='checkbox' name='{$ret_name}' value='{$ret_val}' {$attr} $checked />";
				$html .= "<span>{$ret_text}</span>";
				$html .= "</label>\n";

				/**
				 *
				 * 各チェックボックスごとにかけるフィルター
				 *
				 */
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'class'         => $class,
					'attr'          => $attr,
					'text'          => esc_attr( $get_tags[$i_ele]->name ),
					'value'         => esc_attr( $get_tags[$i_ele]->term_id ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $tag_cnt[$i_ele]->cnt,
				);
				$html = apply_filters( 'feas_tag_checkbox_html', $html, $args );

				// ループ前段に結合
				$ret_ele .= $html;
			}

			/**
			 *
			 * チェックボックスグループ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'ret_ele'       => $ret_ele,
				'show_post_cnt' => $showcnt,
			);
			$ret_ele = apply_filters( 'feas_tag_checkbox_group_html', $ret_ele, $args );

			break;

		/**
		 *	ラジオボタン
		 */
		case '4':
		case 'd':

			/**
			 *	ラジオボタンの「未選択」の表示/非表示
			 */
			$noselect_status = get_option( $cols[31] . $manag_no . '_' . $number );
			if ( $noselect_status ) {

				$ret_ele .= "<label for='feas_" . esc_attr( $manag_no . "_" . $number ) . "_none' class='feas_clevel_01'>";
				$ret_ele .= "<input id='feas_" . esc_attr( $manag_no . "_" . $number ) . "_none' type='radio' name='search_element_" . esc_attr( $number ) . "' value='' />";
				$ret_ele .= "<span>" . esc_html( $noselect_text ) . "</span>";
				$ret_ele .= "</label>\n";
			}

			for ( $i_ele = 0; $i_ele < $cnt_ele; $i_ele++ ) {

				// 0件のタグは表示しない場合
				if ( $nocnt && $tag_cnt[$i_ele]->cnt == 0 )
					continue;

				$checked = '';
				if ( isset( $_GET['fe_form_no'] ) && $_GET['fe_form_no'] == $manag_no ) {
					if ( isset( $_GET['search_element_' . $number] ) ) {
						if ( $_GET['search_element_' . $number] == $get_tags[$i_ele]->term_id ) {
							$checked = ' checked';
						}
					}
				} elseif ( $default_value ) {
					if ( is_array( $default_value ) ) {
						for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
							if ( $default_value[$i_lists] == $get_tags[$i_ele]->term_id ) {
								$checked = ' checked';
							}
						}
					}
				}

				$cat_cnt = '';
				if ( 'yes' == $showcnt ) {
					$cat_cnt = " (" . $tag_cnt[$i_ele]->cnt . ") ";
				}

				$depth = '01';

				// 「要素内の並び順」が「自由記述」の場合、階層に応じてclassを準備
				if ( 'b' === $get_data[$cols[5]] ) {
					$depth = str_pad( $get_tags[$i_ele]->depth, 2, '0', STR_PAD_LEFT );
				}

				/**
				 *
				 * ラジオボタンのclassにかけるフィルター
				 *
				 */
				$class = 'feas_clevel_' . esc_attr( $depth );
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'text'          => esc_attr( $get_tags[$i_ele]->name ),
					'value'         => esc_attr( $get_tags[$i_ele]->term_id ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $tag_cnt[$i_ele]->cnt,
				);
				$class = apply_filters( 'feas_tag_radio_class', $class, $args );

				/**
				 *
				 * 各ラジオボタンのattrにかけるフィルター
				 *
				 */
				$attr = '';
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'class'         => $class,
					'text'          => esc_attr( $get_tags[$i_ele]->name ),
					'value'         => esc_attr( $get_tags[$i_ele]->term_id ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $tag_cnt[$i_ele]->cnt,
				);
				$attr = apply_filters( 'feas_tag_radio_attr', $attr, $args );

				// Sanitize
				$ret_id   = esc_attr( "feas_{$manag_no}_{$number}_{$i_ele}" );
				$ret_name = esc_attr( "search_element_{$number}" );
				$ret_val  = esc_attr( $get_tags[$i_ele]->term_id );
				$ret_text = esc_html( $get_tags[$i_ele]->name . $cat_cnt );

				$html  = "<label for='{$ret_id}' class='{$class}'>";
				$html .= "<input id='{$ret_id}' type='radio' name='{$ret_name}' value='{$ret_val}' {$attr} {$checked} />";
				$html .= "<span>{$ret_text}</span>";
				$html .= "</label>\n";

				/**
				 *
				 * 各ラジオボタンごとにかけるフィルター
				 *
				 */
				$args = array(
					'manag_no'      => (int) $manag_no,
					'number'        => (int) $number,
					'cnt'           => (int) $i_ele,
					'class'         => $class,
					'attr'          => $attr,
					'text'          => esc_attr( $get_tags[$i_ele]->name ),
					'value'         => esc_attr( $get_tags[$i_ele]->term_id ),
					'checked'       => $checked,
					'show_post_cnt' => $showcnt,
					'post_cnt'      => (int) $tag_cnt[$i_ele]->cnt,
				);
				$html = apply_filters( 'feas_tag_radio_html', $html, $args );

				// ループ前段に結合
				$ret_ele .= $html;

			}

			/**
			 *
			 * ラジオボタングループ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no'      => (int) $manag_no,
				'number'        => (int) $number,
				'ret_ele'       => $ret_ele,
				'show_post_cnt' => $showcnt,
			);
			$ret_ele = apply_filters( 'feas_tag_radio_group_html', $ret_ele, $args );

			break;

		/**
		 *	フリーワード
		 */
		case '5':
		case 'e':

			$placeholder_data = '';
			$placeholder = '';
			$output_js = '';

			$placeholder_data = $get_data[$cols[30]];
			if ( $placeholder_data ) {
				$placeholder = ' placeholder="' . esc_attr( $placeholder_data ) . '"';
				$output_js = '<script>jQuery("#feas_' . esc_attr( $manag_no . '_' . $number ) . '").focus( function() { jQuery(this).attr("placeholder",""); }).blur( function() {
    jQuery(this).attr("placeholder", "' . esc_attr( $placeholder_data ) . '"); });</script>';
			}

			$s_keyword = '';
			if ( isset( $_GET['fe_form_no'] ) && $manag_no == $_GET['fe_form_no'] ) {
				if ( isset( $_GET['s_keyword_' . $number] ) ) {
					$s_keyword = $_GET['s_keyword_' . $number];
				}
			} elseif ( $default_value ) {
				if ( is_array( $default_value ) ) {
					for ( $i_lists = 0, $cnt_lists = count( $default_value ); $i_lists < $cnt_lists; $i_lists++ ) {
						if ( '' !== $s_keyword ) {
							$s_keyword .= ' ';
						}
						$s_keyword .= $default_value[$i_lists];
					}
				}
			}

			/**
			 *
			 * inputのclassにかけるフィルター
			 *
			 */
			$class = 'feas_archive_freeword';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$class = apply_filters( 'feas_tag_freeword_class', $class, $args );

			/**
			 *
			 * inputのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$attr = apply_filters( 'feas_tag_freeword_attr', $attr, $args );

			// Sanitize
			$ret_id   = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_name = esc_attr( "s_keyword_{$number}" );
			$ret_val  = esc_attr( stripslashes( $s_keyword ) );

			$html  = "<input type='text' name='{$ret_name}' id='{$ret_id}' class='{$class}' value='{$ret_val}' {$placeholder} {$attr} />\n";
			$html .= $output_js;

			/**
			 *
			 * AND/ORオプション
			 *
			 */
			$andor_html = '';
			$andor_ui_flag = $get_data[$cols[6]];

			if ( 'c' === $andor_ui_flag ) {

				// Sanitize
				$ret_6_id    = esc_attr( "feas_{$manag_no}_{$number}_andor" );
				$ret_6_name  = esc_attr( "feas_andor_{$number}" );

				/**
				 * Filter for class
				 */
				$ret_6_class = 'feas_freeword_andor';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_6_class = esc_attr( apply_filters( 'feas_freeword_andor_class', $ret_6_class, $args ) );

				/**
				 * Filter apply to the text "Exclude"
				 */
				$ret_6_or_text = 'OR';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_6_or_text  = esc_html( apply_filters( 'feas_freeword_andor_or_text', $ret_6_or_text, $args ) );

				$ret_6_and_text = 'AND';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_6_and_text = esc_html( apply_filters( 'feas_freeword_andor_and_text', $ret_6_and_text, $args ) );

				$checked_0 = $checked_1 = '';
				if ( isset( $_GET["{$ret_6_name}"] ) && 'a' === $_GET["{$ret_6_name}"] ) {
					$checked_0 = 'checked';
				} else {
					$checked_1 = 'checked';
				}

				$andor_html  = "<label for='{$ret_6_id}_0' class='{$ret_6_class}'>";
				$andor_html .= "<input type='radio' id='{$ret_6_id}_0' name='{$ret_6_name}' value='a' {$checked_0} />";
				$andor_html .= $ret_6_or_text;
				$andor_html .= "</label>";
				$andor_html .= "<label for='{$ret_6_id}_1' class='{$ret_6_class}'>";
				$andor_html .= "<input type='radio' id='{$ret_6_id}_1' name='{$ret_6_name}' value='b' {$checked_1} />";
				$andor_html .= $ret_6_and_text;
				$andor_html .= "</label>";
			}

			/**
			 *
			 * 除外オプション
			 *
			 */
			$exclude_html = '';
			$exclude_ui_flag = $get_data[$cols[52]];

			if ( '2' === $exclude_ui_flag ) {

				// Sanitize
				$ret_52_id    = esc_attr( "feas_{$manag_no}_{$number}_exclude" );
				$ret_52_name  = esc_attr( "feas_exclude_{$number}" );

				/**
				 * Filter for class
				 */
				$ret_52_class = 'feas_freeword_exclude';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_52_class = esc_attr( apply_filters( 'feas_freeword_exclude_class', $ret_52_class, $args ) );

				/**
				 * Filter apply to the text "Exclude"
				 */
				$ret_52_text = '除外';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_52_text = esc_html( apply_filters( 'feas_freeword_exclude_text', $ret_52_text, $args ) );

				$checked = '';
				if ( isset( $_GET["{$ret_52_name}"] ) && '1' === $_GET["{$ret_52_name}"] ) {
					$checked = 'checked';
				}

				$exclude_html  = "<label for='{$ret_52_id}' class='{$ret_52_class}'>";
				$exclude_html .= "<input type='checkbox' id='{$ret_52_id}' name='{$ret_52_name}' value='1' {$checked} />";
				$exclude_html .= $ret_52_text;
				$exclude_html .= "</label>";
			}

			/**
			 *
			 * 完全一致オプション
			 *
			 */
			$exact_html = '';
			$exact_ui_flag = $get_data[$cols[53]];
			if ( '2' === $exact_ui_flag ) {

				// Sanitize
				$ret_53_id    = esc_attr( "feas_{$manag_no}_{$number}_exact" );
				$ret_53_name  = esc_attr( "feas_exact_{$number}" );

				/*
				 * Filter for class
				 */
				$ret_53_class = 'feas_freeword_exact';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_53_class = esc_attr( apply_filters( 'feas_freeword_exact_class', $ret_53_class, $args ) );

				/*
				 * Filter apply to the text "Exclude"
				 */
				$ret_53_text = '完全一致';
				$args = array(
					'manag_no' => (int) $manag_no,
					'number'   => (int) $number,
				);
				$ret_53_text = esc_html( apply_filters( 'feas_freeword_exact_text', $ret_53_text, $args ) );

				$checked = '';
				if ( isset( $_GET["{$ret_53_name}"] ) && '1' === $_GET["{$ret_53_name}"] ) {
					$checked = 'checked';
				}

				$exact_html  = "<label for='{$ret_53_id}' class='{$ret_53_class}'>";
				$exact_html .= "<input type='checkbox' id='{$ret_53_id}' name='{$ret_53_name}' value='1' {$checked} />";
				$exact_html .= $ret_53_text;
				$exact_html .= "</label>";
			}

			if ( 'c' === $andor_ui_flag || '2' === $exclude_ui_flag || '2' === $exact_ui_flag ) {

				$tmp_html  = '<div class="feas_inline_group">';
				$tmp_html .= $html;
				$tmp_html .= '<div class="feas_wrap_options">';
				$tmp_html .= $andor_html . $exclude_html . $exact_html;
				$tmp_html .= '</div>';
				$tmp_html .= "</div>";

				$html = $tmp_html;
			}

			if ( '' !== $get_data[$cols[20]] ) {
				$html .= create_specifies_the_key_element( $get_data, $number );
			}

			/**
			 *
			 * inputタグ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'attr'     => $attr,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$html = apply_filters( 'feas_tag_freeword_group_html', $html, $args );

			$ret_ele .= $html;

			break;

		/**
		 *	グループ
		 */
		case 'f':

			break;

		/**
		 *	その他
		 */
		default:

			$s_keyword = '';
			if ( isset( $_GET['fe_form_no'] ) && $manag_no == $_GET['fe_form_no'] ) {
				if ( isset( $_GET['s_keyword_' . $number] ) ) {
					$s_keyword = $_GET['s_keyword_' . $number];
				}
			}

			/**
			 *
			 * inputのclassにかけるフィルター
			 *
			 */
			$class = 'feas_archive_freeword';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$class = apply_filters( 'feas_tag_freeword_class', $class, $args );

			/**
			 *
			 * inputのattrにかけるフィルター
			 *
			 */
			$attr = '';
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$attr = apply_filters( 'feas_tag_freeword_attr', $attr, $args );

			// Sanitize
			$ret_id   = esc_attr( "feas_{$manag_no}_{$number}" );
			$ret_name = esc_attr( "s_keyword_{$number}" );
			$ret_val  = esc_attr( stripslashes( $s_keyword ) );

			$html  = "<input type='text' name='{$ret_name}' id='{$ret_id}' class='{$class}' value='{$ret_val}' {$placeholder} {$attr} />\n";
			$html .= $output_js;

			/**
			 *
			 * inputタグ全体にかけるフィルター
			 *
			 */
			$args = array(
				'manag_no' => (int) $manag_no,
				'number'   => (int) $number,
				'class'    => $class,
				'attr'     => $attr,
				'value'    => esc_attr( stripslashes( $s_keyword ) ),
			);
			$html = apply_filters( 'feas_tag_freeword_group_html', $html, $args );

			$ret_ele .= $html;

			break;
	}

	return $ret_ele;
}

/*============================
	フリーワード検索時 カスタムフィールドのキー限定
 ============================*/
function create_specifies_the_key_element( $get_data, $number ) {
	global $wpdb, $cols, $feadvns_show_count, $manag_no, $feadvns_include_sticky;

	$html = null;

	// キーの配列
	$meta_keys = $get_data[ 'feadvns_cf_specify_key_' ];

	if ( is_array( $meta_keys ) ) {

		$i_ele = 0;

		foreach ( $meta_keys as $val ) {
			$html .= "<input type='hidden' name='cf_specify_key_" . esc_attr( $number ) . "_" . esc_attr( $i_ele ) . "' value='" . esc_attr( $val ) . "' />";
			$i_ele++;
		}
		$html .= "<input type='hidden' name='cf_specify_key_length_" . esc_attr( $number ) . "' value='" . ( esc_attr( $i_ele ) - 1 ) . "'/>";

		/**
		 *
		 * hiddenタグの追加など
		 *
		 */
		$args = array(
			'manag_no' => (int) $manag_no,
			'number'   => (int) $number,
		);
		$html = apply_filters( 'feas_freeword_specifies_key_after_html', $html, $args );

	}

	return $html;
}
