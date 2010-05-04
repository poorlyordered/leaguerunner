<?php
/*
 * Handle operations specific to leagues
 */

function league_dispatch()
{
	$op = arg(1);
	$id = arg(2);
	switch($op) {
		case 'create':
			return new LeagueCreate;
		case 'list':
			return new LeagueList;
		case 'edit':
			$obj = new LeagueEdit;
			break;
		case 'view':
			$obj = new LeagueView;
			break;
		case 'delete':
			$obj = new LeagueDelete;
			break;
		case 'standings':
			$obj = new LeagueStandings;
			break;
		case 'captemail':
			$obj = new LeagueCaptainEmails;
			break;
		case 'approvescores':
			$obj = new LeagueApproveScores;
			break;
		case 'member':
			$obj = new LeagueMemberStatus;
			break;
		case 'spirit':
			$obj = new LeagueSpirit;
			break;
		case 'spiritdownload':
			$obj = new LeagueSpiritDownload;
			break;
		case 'ratings':
			$obj = new LeagueRatings;
			break;
		case 'status':
			$obj = new LeagueStatusReport;
			break;
		case 'fields':
			$obj = new LeagueFieldReport;
			break;
		case 'scores':
			$obj = new LeagueScoresTable;
			break;
		case 'slots':
			$obj = new LeagueFieldAvailability;
			break;
		default:
			return null;
	}

	$obj->league = league_load( array('league_id' => $id) );
	if( ! $obj->league ){
		error_exit("That league does not exist");
	}
	league_add_to_menu($obj->league);
	return $obj;
}

function league_permissions( $user, $action, $id, $data_field = '' )
{
	// TODO: finish this!
	switch($action)
	{
		case 'view':
			switch($data_field) {
				case 'spirit':
				case 'captain emails':
				case 'delays':
					return ($user && $user->is_coordinator_of($id));
				default:
					return true;
			}
			break;
		case 'list':
			return true;
		case 'edit':
		case 'edit game':
		case 'add game':
		case 'approve scores':
		case 'edit schedule':
		case 'manage teams':
		case 'ratings':
			return ($user && $user->is_coordinator_of($id));
		case 'create':
		case 'delete':
		case 'download':
			// admin only
			break;
	}
	return false;
}

function league_menu()
{
	global $lr_session;

	if( !$lr_session->is_player() ) {
		return;
	}

	menu_add_child('_root','league','Leagues');
	menu_add_child('league','league/list','list leagues', array('link' => 'league/list') );
	if( $lr_session->is_valid() ) {
		while(list(,$league) = each($lr_session->user->leagues) ) {
			league_add_to_menu($league);
		}
		reset($lr_session->user->leagues);
	}
	if($lr_session->has_permission('league','create') ) {
		menu_add_child('league', 'league/create', "create league", array('link' => "league/create", 'weight' => 1));
	}
}

/**
 * Add view/edit/delete links to the menu for the given league
 */
function league_add_to_menu( &$league, $parent = 'league' )
{
	global $lr_session;

	menu_add_child($parent, $league->fullname, $league->fullname, array('weight' => -10, 'link' => "league/view/$league->league_id"));

	if($league->schedule_type != 'none') {
		menu_add_child($league->fullname, "$league->fullname/standings",'standings', array('weight' => -1, 'link' => "league/standings/$league->league_id"));
		menu_add_child($league->fullname, "$league->fullname/schedule",'schedule', array('weight' => -1, 'link' => "schedule/view/$league->league_id"));
		menu_add_child($league->fullname, "$league->fullname/scores",'scores', array('weight' => -1, 'link' => "league/scores/$league->league_id"));
		if($lr_session->has_permission('league','add game', $league->league_id) ) {
			menu_add_child("$league->fullname/schedule", "$league->fullname/schedule/edit", 'add games', array('link' => "game/create/$league->league_id"));
		}
		if($lr_session->has_permission('league','approve scores', $league->league_id) ) {
			menu_add_child($league->fullname, "$league->fullname/approvescores",'approve scores', array('weight' => 1, 'link' => "league/approvescores/$league->league_id"));
		}
	}

	if($lr_session->has_permission('league','edit', $league->league_id) ) {
		menu_add_child($league->fullname, "$league->fullname/edit",'edit league', array('weight' => 1, 'link' => "league/edit/$league->league_id"));
		if ( $league->schedule_type == "ratings_ladder" || $league->schedule_type == 'ratings_wager_ladder' ) {
			menu_add_child($league->fullname, "$league->fullname/ratings",'adjust ratings', array('weight' => 1, 'link' => "league/ratings/$league->league_id"));
		}
		menu_add_child($league->fullname, "$league->fullname/member",'add coordinator', array('weight' => 2, 'link' => "league/member/$league->league_id"));
	}

	if($lr_session->has_permission('league','view', $league->league_id, 'captain emails') ) {
		menu_add_child($league->fullname, "$league->fullname/captemail",'captain emails', array('weight' => 3, 'link' => "league/captemail/$league->league_id"));
	}

	if($lr_session->has_permission('league','view', $league->league_id, 'spirit') ) {
		menu_add_child($league->fullname, "$league->fullname/spirit",'spirit', array('weight' => 3, 'link' => "league/spirit/$league->league_id"));
	}
	if($lr_session->has_permission('league', 'download', $league->league_id, 'spirit') ) {
		menu_add_child($league->fullname, "$league->fullname/spirit_download",'spirit report', array('weight' => 3, 'link' => "league/spiritdownload/$league->league_id"));
	}
	if($lr_session->has_permission('league','edit', $league->league_id) ) {
		if ( $league->schedule_type == "ratings_ladder" || $league->schedule_type == 'ratings_wager_ladder' ) {
			menu_add_child($league->fullname, "$league->fullname/status",'status report', array('weight' => 1, 'link' => "league/status/$league->league_id"));
		}
	}
	if($lr_session->has_permission('league','edit', $league->league_id) ) {
		menu_add_child($league->fullname, "$league->fullname/fields",'field distribution', array('weight' => 1, 'link' => "league/fields/$league->league_id"));
		menu_add_child($league->fullname, "$league->fullname/slots",'available fields', array('weight' => 1, 'link' => "league/slots/$league->league_id"));
	}
}

/**
 * Generate view of leagues for initial login splash page.
 */
function league_splash ()
{
	global $lr_session;
	if( ! $lr_session->user->is_a_coordinator ) {
		return;
	}

	$header = array(
		array( 'data' => "Leagues Coordinated", 'colspan' => 4)
	);
	$rows = array();

	// TODO: For each league, need to display # of missing scores,
	// pending scores, etc.
	while(list(,$league) = each($lr_session->user->leagues)) {
		$links = array(
			l("edit", "league/edit/$league->league_id")
		);
		if($league->schedule_type != 'none') {
			$links[] = l("schedule", "schedule/view/$league->league_id");
			$links[] = l("standings", "league/standings/$league->league_id");
			$links[] = l("approve scores", "league/approvescores/$league->league_id");
		}

		$rows[] = array(
			array(
				'data' => l($league->fullname, "league/view/$league->league_id"),
				'colspan' => 3
			),
			array(
				'data' => theme_links($links),
				'align' => 'right'
			)
		);
	}
	reset($lr_session->user->leagues);

	return table( $header, $rows );
}

/**
 * Periodic tasks to perform.  This should handle any internal checkpointing
 * necessary, as the cron task may be called more or less frequently than we
 * expect.
 */
function league_cron()
{
	global $dbh;

	$output = '';

	$sth = $dbh->prepare('SELECT DISTINCT league_id FROM league WHERE status = ? AND season != ? ORDER BY season, day, tier, league_id');
	$sth->execute( array('open', 'none') );
	while( $id = $sth->fetchColumn() ) {
		$league = league_load( array('league_id' => $id) );
		$output .= h2(l($league->name, "league/view/$league->league_id"));

		// Find all games older than our expiry time, and finalize them
		$output .= $league->finalize_old_games();

		// Send any email scoring reminders. Do this after finalizing, so
		// captains don't get useless reminders.
		$output .= $league->send_scoring_reminders();

		// If schedule is round-robin, possibly update the current round
		if($league->schedule_type == 'roundrobin') {
			$league->update_current_round();
		}
	}

	return "$output<pre>Completed league_cron run</pre>";
}

/**
 * Create handler
 */
class LeagueCreate extends LeagueEdit
{
	var $league;

	function has_permission()
	{
		global $lr_session;
		return $lr_session->has_permission('league','create');
	}

	function process ()
	{
		$id = -1;
		$edit = $_POST['edit'];
		$this->title = "Create League";

		switch($edit['step']) {
			case 'confirm':
				$rc = $this->generateConfirm( $edit );
				break;
			case 'perform':
				$this->league = new League;
				$this->perform( $edit );
				local_redirect(url("league/view/" . $this->league->league_id));
				break;
			default:
				$edit = array();
				$rc = $this->generateForm( $edit );
		}
		$this->setLocation(array($this->title => 0));
		return $rc;
	}

	function perform ( $edit )
	{
		global $lr_session;

		$dataInvalid = $this->isDataInvalid( $edit );
		if($dataInvalid) {
			error_exit($dataInvalid . "<br>Please use your back button to return to the form, fix these errors, and try again");
		}

		$this->league->set('name',$lr_session->attr_get('user_id'));
		$this->league->add_coordinator($lr_session->user);

		return parent::perform($edit);
	}
}

/**
 * League edit handler
 */
class LeagueEdit extends Handler
{
	var $league;

	function has_permission()
	{
		global $lr_session;
		return $lr_session->has_permission('league','edit',$this->league->league_id);
	}

	function process ()
	{
		$this->title = "Edit League";

		$edit = &$_POST['edit'];

		switch($edit['step']) {
			case 'confirm':
				$rc = $this->generateConfirm( $edit );
				break;
			case 'perform':
				$this->perform($edit);
				local_redirect(url("league/view/" . $this->league->league_id));
				break;
			default:
				$edit = $this->getFormData( $this->league );
				$rc = $this->generateForm( $edit );
		}
		$this->setLocation(array( $edit['name'] => "league/view/" . $this->league->league_id, $this->title => 0));

		return $rc;
	}

	function getFormData ( &$league )
	{
		/* Deal with multiple days and start times */
		if(strpos($league->day, ",")) {
			$league->day = split(",",$league->day);
		}
		return object2array($league);
	}

	function generateForm ( &$formData )
	{
		$output .= form_hidden('edit[step]', 'confirm');

		$rows = array();
		$rows[] = array('League Name:', form_textfield('', 'edit[name]', $formData['name'], 35,200, 'The full name of the league.  Tier numbering will be automatically appended.'));

		$rows[] = array('Status:',
			form_select('', 'edit[status]', $formData['status'], getOptionsFromEnum('league','status'), 'Teams in closed leagues are locked and can be viewed only in historical modes'));

		$rows[] = array('Year:', form_textfield('', 'edit[year]', $formData['year'], 4,4, 'Year of play.'));

		$rows[] = array('Season:',
			form_select('', 'edit[season]', $formData['season'], getOptionsFromEnum('league','season'), "Season of play for this league. Choose 'none' for administrative groupings and comp teams."));

		$rows[] = array('Day(s) of play:',
			form_select('', 'edit[day]', $formData['day'], getOptionsFromEnum('league','day'), 'Day, or days, on which this league will play.', 0, true));

		$thisYear = strftime('%Y', time());
		$rows[] = array('Roster deadline:',
			form_select_date('', 'edit[roster_deadline]', $formData['roster_deadline'], ($thisYear - 1), ($thisYear + 1), 'The date after which teams are no longer allowed to edit their rosters.'));

		/* TODO: 10 is a magic number.  Make it a config variable */
		$rows[] = array('Tier:',
			form_select('', 'edit[tier]', $formData['tier'], getOptionsFromRange(0, 10), 'Tier number.  Choose 0 to not have numbered tiers.'));

		$rows[] = array('Gender Ratio:',
			form_select('', 'edit[ratio]', $formData['ratio'], getOptionsFromEnum('league','ratio'), 'Gender format for the league.'));

		/* TODO: 5 is a magic number.  Make it a config variable */
		$rows[] = array('Current Round:',
			form_select('', 'edit[current_round]', $formData['current_round'], getOptionsFromRange(1, 5), 'New games will be scheduled in this round by default.'));

		$rows[] = array('Scheduling Type:',
			form_select('', 'edit[schedule_type]', $formData['schedule_type'], getOptionsFromEnum('league','schedule_type'), 'What type of scheduling to use.  This affects how games are scheduled and standings displayed.'));

		$rows[] = array('Ratings - Games Before Repeat:',
			form_select('', 'edit[games_before_repeat]', $formData['games_before_repeat'], getOptionsFromRange(0,9), 'The number of games before two teams can be scheduled to play each other again (FOR PYRAMID/RATINGS LADDER SCHEDULING ONLY).'));

		$rows[] = array('How to enter SOTG?',
			form_select('', 'edit[enter_sotg]', $formData['enter_sotg'], getOptionsFromEnum('league','enter_sotg'), 'Control SOTG entry.  "both" uses the survey and allows numeric input.  "numeric_only" turns off the survey for spirit.  "survey_only" uses only the survey questions to gather SOTG info.'));

		$rows[] = array('How to display SOTG?',
			form_select('', 'edit[display_sotg]', $formData['display_sotg'], getOptionsFromEnum('league','display_sotg'), 'Control SOTG display.  "all" shows numeric scores and survey answers to any player.  "symbols_only" shows only star, check, and X, with no numeric values attached.  "coordinator_only" restricts viewing of any per-game information to coordinators only.'));

		$rows[] = array('League Coordinator Email List:', form_textfield('', 'edit[coord_list]', $formData['coord_list'], 35,200, 'An email alias for all coordinators of this league (can be a comma separated list of individual email addresses)'));

		$rows[] = array('League Captain Email List:', form_textfield('', 'edit[capt_list]', $formData['capt_list'], 35,200, 'An email alias for all captains of this league'));

		$rows[] = array('Allow exclusion of teams during scheduling?', 
			form_select('', 'edit[excludeTeams]', $formData['excludeTeams'], getOptionsFromEnum('league','excludeTeams'), 'Allows coordinators to exclude teams from schedule generation.'));

		$rows[] = array('Scoring reminder delay:', form_textfield('', 'edit[email_after]', $formData['email_after'], 5, 5, 'Email captains who haven\'t scored games after this many hours, no reminder if 0'));

		$rows[] = array('Game finalization delay:', form_textfield('', 'edit[finalize_after]', $formData['finalize_after'], 5, 5, 'Games which haven\'t been scored will be automatically finalized after this many hours, no finalization if 0'));

		$output .= '<div class="pairtable">' . table(null, $rows) . '</div>';
		$output .= para(form_submit('submit') . form_reset('reset'));

		return form($output);
	}

	function generateConfirm ( $edit )
	{
		$dataInvalid = $this->isDataInvalid( $edit );
		if($dataInvalid) {
			error_exit($dataInvalid . "<br>Please use your back button to return to the form, fix these errors, and try again");
		}

		if(is_array($edit['day'])) {
			$edit['day'] = join(",",$edit['day']);
		}

		$output = para("Confirm that the data below is correct and click 'Submit' to make your changes.");
		$output .= form_hidden("edit[step]", 'perform');

		$rows = array();
		$rows[] = array("League Name:",
			form_hidden('edit[name]', $edit['name']) . $edit['name']);

		$rows[] = array("Status:",
			form_hidden('edit[status]', $edit['status']) . $edit['status']);

		$rows[] = array("Year:",
			form_hidden('edit[year]', $edit['year']) . $edit['year']);

		$rows[] = array("Season:",
			form_hidden('edit[season]', $edit['season']) . $edit['season']);

		$rows[] = array("Day(s) of play:",
			form_hidden('edit[day]',$edit['day']) . $edit['day']);

		$rows[] = array("Roster deadline:",
			form_hidden('edit[roster_deadline][year]',$edit['roster_deadline']['year'])
			. form_hidden('edit[roster_deadline][month]',$edit['roster_deadline']['month'])
			. form_hidden('edit[roster_deadline][day]',$edit['roster_deadline']['day'])
			. $edit['roster_deadline']['year'] . '/' . $edit['roster_deadline']['month'] . '/' . $edit['roster_deadline']['day']);

		$rows[] = array("Tier:",
			form_hidden('edit[tier]', $edit['tier']) . $edit['tier']);

		$rows[] = array("Gender Ratio:",
			form_hidden('edit[ratio]', $edit['ratio']) . $edit['ratio']);

		$rows[] = array("Current Round:",
			form_hidden('edit[current_round]', $edit['current_round']) . $edit['current_round']);

		$rows[] = array("Scheduling Type:",
			form_hidden('edit[schedule_type]', $edit['schedule_type']) . $edit['schedule_type']);

		if (   $edit['schedule_type'] == 'ratings_ladder'
		    || $edit['schedule_type'] == 'ratings_wager_ladder') {
			$rows[] = array("Ratings - Games Before Repeat:",
				form_hidden('edit[games_before_repeat]', $edit['games_before_repeat']) . $edit['games_before_repeat']);
		}
		$rows[] = array("How to enter SOTG?",
			form_hidden('edit[enter_sotg]', $edit['enter_sotg']) . $edit['enter_sotg']);

		$rows[] = array("How to display SOTG?",
			form_hidden('edit[display_sotg]', $edit['display_sotg']) . $edit['display_sotg']);

		$rows[] = array("League Coordinator Email List:", 
			form_hidden('edit[coord_list]', $edit['coord_list']) . $edit['coord_list']);

		$rows[] = array("League Captain Email List:", 
			form_hidden('edit[capt_list]', $edit['capt_list']) . $edit['capt_list']);

		$rows[] = array("Allow exclusion of teams during scheduling?", 
			form_hidden('edit[excludeTeams]', $edit['excludeTeams']) . $edit['excludeTeams']);

		$rows[] = array('Scoring reminder delay:',
			form_hidden('edit[email_after]', $edit['email_after']) . $edit['email_after']);

		$rows[] = array('Game finalization delay:',
			form_hidden('edit[finalize_after]', $edit['finalize_after']) . $edit['finalize_after']);

		$output .= "<div class='pairtable'>" . table(null, $rows) . "</div>";
		$output .= para(form_submit("submit"));

		return form($output);
	}

	function perform ( $edit )
	{
		$dataInvalid = $this->isDataInvalid( $edit );
		if($dataInvalid) {
			error_exit($dataInvalid . "<br>Please use your back button to return to the form, fix these errors, and try again");
		}

		$this->league->set('name', $edit['name']);
		$this->league->set('status', $edit['status']);
		$this->league->set('day', $edit['day']);
		$this->league->set('year', $edit['year']);
		$this->league->set('season', $edit['season']);
		$this->league->set('roster_deadline', join('-',array(
								$edit['roster_deadline']['year'],
								$edit['roster_deadline']['month'],
								$edit['roster_deadline']['day'])));
		$this->league->set('tier', $edit['tier']);
		$this->league->set('ratio', $edit['ratio']);
		$this->league->set('current_round', $edit['current_round']);
		$this->league->set('schedule_type', $edit['schedule_type']);

		if (   $edit['schedule_type'] == 'ratings_ladder'
		    || $edit['schedule_type'] == 'ratings_wager_ladder') {
			$this->league->set('games_before_repeat', $edit['games_before_repeat']);
		}

		$this->league->set('enter_sotg', $edit['enter_sotg']);
		$this->league->set('display_sotg', $edit['display_sotg']);
		$this->league->set('coord_list', $edit['coord_list']);
		$this->league->set('capt_list', $edit['capt_list']);
		$this->league->set('excludeTeams', $edit['excludeTeams']);

		$this->league->set('email_after', $edit['email_after']);
		$this->league->set('finalize_after', $edit['finalize_after']);

		if( !$this->league->save() ) {
			error_exit("Internal error: couldn't save changes");
		}

		return true;
	}

	/* TODO: Properly validate other data */
	function isDataInvalid ( $edit )
	{
		$errors = "";

		if ( ! validate_nonhtml($edit['name'])) {
			$errors .= "<li>A valid league name must be entered";
		}

		if( !validate_date_input($edit['roster_deadline']['year'], $edit['roster_deadline']['month'], $edit['roster_deadline']['day']) )
		{
			$errors .= '<li>You must provide a valid roster deadline';
		}

		switch($edit['schedule_type']) {
			case 'none':
			case 'roundrobin':
				break;
			case 'ratings_ladder':
			case 'ratings_wager_ladder':
				if ($edit['games_before_repeat'] == null || $edit['games_before_repeat'] == 0) {
					$errors .= "<li>Invalid 'Games Before Repeat' specified!";
				}
				break;
			default:
				$errors .= "<li>Values for allow schedule are none, roundrobin, ratings_ladder, and ratings_wager_ladder";
		}

		if($edit['schedule_type'] != 'none') {
			if( !$edit['day'] ) {
				$errors .= "<li>One or more days of play must be selected";
			}
		}

		if ( ! validate_number($edit['email_after']) || $edit['email_after'] < 0 ) {
			$errors .= "<li>A valid number must be entered for the scoring reminder delay";
		}

		if ( ! validate_number($edit['finalize_after']) || $edit['finalize_after'] < 0 ) {
			$errors .= "<li>A valid number must be entered for the game finalization delay";
		}

		if(strlen($errors) > 0) {
			return $errors;
		} else {
			return false;
		}
	}
}

/**
 * League list handler
 */
class LeagueList extends Handler
{
	function has_permission ()
	{
		global $lr_session;
		return $lr_session->has_permission('league','list');
	}

	function process ()
	{
		global $lr_session;

		$season = arg(2);
		if( ! $season ) {
			$season = strtolower(variable_get('current_season', "Summer"));
		}

		/* Fetch league names */
		$seasons = getOptionsFromEnum('league', 'season');

		$seasonLinks = array();
		foreach($seasons as $curSeason) {
			$curSeason = strtolower($curSeason);
			if($curSeason == '---') {
				continue;
			}
			if($curSeason == $season) {
				$seasonLinks[] = $curSeason;
			} else {
				$seasonLinks[] = l($curSeason, "league/list/$curSeason");
			}
		}

		$this->setLocation(array(
			$season => "league/list/$season"
		));

		$output = para(theme_links($seasonLinks));

		$header = array( "Name", "&nbsp;") ;
		$rows = array();

		$leagues = league_load_many( array( 'season' => $season, 'status' => 'open', '_order' => "year,FIELD(MAKE_SET((day & 62), 'BUG','Monday','Tuesday','Wednesday','Thursday','Friday'),'Monday','Tuesday','Wednesday','Thursday','Friday'), tier, league_id") );

		if ( $leagues ) {
			foreach ( $leagues as $league ) {
				$links = array();
				if($league->schedule_type != 'none') {
					$links[] = l('schedule',"schedule/view/$league->league_id");
					$links[] = l('standings',"league/standings/$league->league_id");
				}
				if( $lr_session->has_permission('league','delete', $league->league_id) ) {
					$links[] = l('delete',"league/delete/$league->league_id");
				}
				$rows[] = array(
					l($league->fullname,"league/view/$league->league_id"),
					theme_links($links));
			}

			$output .= "<div class='listtable'>" . table($header, $rows) . "</div>";
		}

		return $output;
	}
}

class LeagueStandings extends Handler
{
	var $league;

	function has_permission ()
	{
		global $lr_session;
		return $lr_session->has_permission('league','view', $this->league->league_id);
	}

	function process ()
	{
		global $lr_session;

		$id = arg(2);
		$teamid = arg(3);
		$showall = arg(4);

		$this->title = "Standings";

		if($this->league->schedule_type == 'none') {
			error_exit("This league does not have a schedule or standings.");
		}

		$s = new Spirit;
		$s->entry_type = $this->league->enter_sotg;
		$s->display_numeric_sotg = $this->league->display_numeric_sotg();

		$round = $_GET['round'];
		if(! isset($round) ) {
			$round = $this->league->current_round;
		}
		// check to see if this league is on round 2 or higher...
		// if so, set the $current_round so that the standings table is split up
		if ($round > 1) {
			$current_round = $round;
		}

		$this->setLocation(array(
			$this->league->fullname => "league/view/$id",
			$this->title => 0,
		));

		list($order, $season, $round) = $this->league->calculate_standings(array( 'round' => $current_round ));


 		// let's add "seed" into the mix:
		$seeded_order = array();
		for ($i = 0; $i < count($order); $i++) {
			$seeded_order[$i+1] = $order[$i];
		}
		//reset($order);
		$order = $seeded_order;

		// if this is a ratings ladder league and  we're asking for "team" standings, only show
		// the 5 teams above and 5 teams below this team ... don't bother if there are
		// 24 teams or less (24 is probably the largest fall league size)... and, if $showall
		// is set, don't remove items from $order.
		$more_before = 0;
		$more_after = 0;
		if ( ($showall == null || $showall == 0 || $showall == "")
		    && $teamid != null 
		    && $teamid != "" 
		    && ( $this->league->schedule_type == "ratings_ladder"
			 || $this->league->schedule_type == "ratings_wager_ladder") 
			&& count($order) > 24) {
			$index_of_this_team = 0;
			foreach ($order as $i => $value) {
				if ($value == $teamid) {
					$index_of_this_team = $i;
					break;
				}
			}
			reset($order);
			$count = count($order);
			// use "unset($array[$index])" to remove unwanted elements of the order array
			for ($i = 1; $i < $count+1; $i++) {
				if ($i < $index_of_this_team - 5 || $i > $index_of_this_team + 5) {
					unset($order[$i]);
					if ($i < $index_of_this_team - 5) {
						$more_before = 1;
					}
					if ($i > $index_of_this_team + 5) {
						$more_after = 1;
					}
				}
			}
			reset($order);
		}

		/* Build up header */
		$header = array( array('data' => 'Seed', 'rowspan' => 2) );
		$header[] = array( 'data' => 'Team', 'rowspan' => 2 );
		if( $this->league->schedule_type == "ratings_ladder"
		    || $this->league->schedule_type == "ratings_wager_ladder" ) {
			$header[] = array('data' => "Rating", 'rowspan' => 2);
		}

		$subheader = array();

		if( variable_get('narrow_display', '0') ) {
			$win = 'W';
			$loss = 'L';
			$tie = 'T';
			$default = 'D';
			$for = 'PF';
			$against = 'PA';
		} else {
			$win = 'Win';
			$loss = 'Loss';
			$tie = 'Tie';
			$default = 'Dfl';
			$for = 'PF';
			$against = 'PA';
		}

		// Ladder leagues display standings differently.
		// Eventually this should just be a brand new object.
		if( $this->league->schedule_type == "ratings_ladder"
		    || $this->league->schedule_type == "ratings_wager_ladder" ) {
			$header[] = array('data' => 'Season To Date', 'colspan' => 7); 
			foreach(array($win, $loss, $tie, $default, $for, $against, "+/-") as $text) {
				$subheader[] = array('data' => $text, 'class'=>'subtitle', 'valign'=>'bottom');
			}
		} else {
			if($current_round) {
				$header[] = array('data' => "Current Round ($current_round)", 'colspan' => 7);
				foreach(array($win, $loss, $tie, $default, $for, $against, "+/-") as $text) {
					$subheader[] = array('data' => $text, 'class'=>'subtitle', 'valign'=>'bottom');
				}
			}

			$header[] = array('data' => 'Season To Date', 'colspan' => 7);
			foreach(array($win, $loss, $tie, $default, $for, $against, "+/-") as $text) {
				$subheader[] = array('data' => $text, 'class'=>'subtitle', 'valign'=>'bottom');
			}
		}

		$header[] = array('data' => "Streak", 'rowspan' => 2);
		$header[] = array('data' => "Avg.<br>SOTG", 'rowspan' => 2);

		$rows[] = $subheader;

		if ($more_before) {
			$rows[] = array(array( 'data' => l("... ... ...", "league/standings/$id/$teamid/1"), 'colspan' => 13, 'align' => 'center'));
		}

		// boolean for coloration of standings table
		$colored = false;
		$firsttimethrough = true;

		while(list($seed, $tid) = each($order)) {

			if ($firsttimethrough) {
				$firsttimethrough = false;
				for ($i = 1; $i < $seed; $i++) {
					if ($i %8 == 0) {
						$colored = !$colored;
					}
				}
			}
			$rowstyle = "none";
			if ($colored) {
				$rowstyle = "tierhighlight";
			}
			if ($seed % 8 == 0) {
				$colored = !$colored;
			}
			if ($teamid == $tid) {
				if ($rowstyle == "none") {
					$rowstyle = "teamhighlight";
				} else {
					$rowstyle = "tierhighlightteam";
				}
			}
			$row = array( array('data'=>"$seed", 'class'=>"$rowstyle"));
			$row[] = array( 'data'=>l(display_short_name($season[$tid]->name, 35), "team/view/$tid"), 'class'=>"$rowstyle");

			// Don't need the current round for a ladder schedule.
			if ($this->league->schedule_type == "roundrobin") {
				if($current_round) {
					$old_rowstyle = $rowstyle;
					$rowstyle = "standings";
					if ($tid == $teamid) {
						$rowstyle = "teamhighlight";
					}
					$row[] = array( 'data' => $round[$tid]->win, 'class'=>"$rowstyle");
					$row[] = array( 'data' => $round[$tid]->loss, 'class'=>"$rowstyle");
					$row[] = array( 'data' => $round[$tid]->tie, 'class'=>"$rowstyle");
					$row[] = array( 'data' => $round[$tid]->defaults_against, 'class'=>"$rowstyle");
					$row[] = array( 'data' => $round[$tid]->points_for, 'class'=>"$rowstyle");
					$row[] = array( 'data' => $round[$tid]->points_against, 'class'=>"$rowstyle");
					$row[] = array( 'data' => $round[$tid]->points_for - $round[$tid]->points_against, 'class'=>"$rowstyle");
					$rowstyle = $old_rowstyle;
				}
			}

			if ($this->league->schedule_type == "ratings_ladder" 
			    || $this->league->schedule_type == "ratings_wager_ladder" ) {
				$row[] = array( 'data' => $season[$tid]->rating, 'class'=>"$rowstyle");
			}
			$row[] = array( 'data' => $season[$tid]->win, 'class'=>"$rowstyle");
			$row[] = array( 'data' => $season[$tid]->loss, 'class'=>"$rowstyle");
			$row[] = array( 'data' => $season[$tid]->tie, 'class'=>"$rowstyle");
			$row[] = array( 'data' => $season[$tid]->defaults_against, 'class'=>"$rowstyle");
			$row[] = array( 'data' => $season[$tid]->points_for, 'class'=>"$rowstyle");
			$row[] = array( 'data' => $season[$tid]->points_against, 'class'=>"$rowstyle");
			$row[] = array( 'data' => $season[$tid]->points_for - $season[$tid]->points_against, 'class'=>"$rowstyle");

			if( count($season[$tid]->streak) > 1 ) {
				$row[] = array( 'data' => count($season[$tid]->streak) . $season[$tid]->streak[0], 'class'=>"$rowstyle");
			} else {
				$row[] = array( 'data' => '-', 'class'=>"$rowstyle");
			}


			$avg = $s->average_sotg( $season[$tid]->spirit, false);
			$symbol = $s->full_spirit_symbol_html( $avg );
			$row[] = array(
				'data' => $symbol,
				'class'=>"$rowstyle");
			$rows[] = $row;
		}

		if ($more_after) {
			$rows[] = array(array( 'data' => l("... ... ...", "league/standings/$id/$teamid/1"), 'colspan' => 13, 'align' => 'center'));
		}

		return "<div class='listtable'>" . table($header, $rows) . "</div>";
	}
}

/**
 * League viewing handler
 */
class LeagueView extends Handler
{
	var $league;

	function has_permission()
	{
		global $lr_session;
		return $lr_session->has_permission('league','view',$this->league->league_id);
	}

	function process ()
	{
		global $lr_session;

		$this->title = 'View League';

		foreach( $this->league->coordinators as $c ) {
			$coordinator = l($c->fullname, "person/view/$c->user_id");
			if($lr_session->has_permission('league','edit',$this->league->league_id)) {
				$coordinator .= "&nbsp;[&nbsp;" . l('remove coordinator', url("league/member/" . $this->league->league_id."/$c->user_id", 'edit[status]=remove')) . "&nbsp;]";
			}
			$coordinators[] = $coordinator;
		}
		reset($this->league->coordinators);

		$rows = array();
		if( count($coordinators) ) {
			$rows[] = array('Coordinators:',
				join('<br />', $coordinators));
		}

		if ($this->league->coord_list != null && $this->league->coord_list != '') {
			$rows[] = array('Coordinator Email List:', l($this->league->coord_list, "mailto:" . $this->league->coord_list));
		}
		if ($this->league->capt_list != null && $this->league->capt_list != '') {
			$rows[] = array('Captain Email List:', l($this->league->capt_list, "mailto:" . $this->league->capt_list));
		}

		$rows[] = array('Status:', $this->league->status);
		if($this->league->year) {
			$rows[] = array('Year:', $this->league->year);
		}
		$rows[] = array('Season:', $this->league->season);
		if($this->league->day) {
			$rows[] = array('Day(s):', $this->league->day);
		}
		if($this->league->roster_deadline) {
			$rows[] = array('Roster deadline:', $this->league->roster_deadline);
		}
		if($this->league->tier) {
			$rows[] = array('Tier:', $this->league->tier);
		}
		$rows[] = array('Type:', $this->league->schedule_type);

		// Certain things should only be visible for certain types of league.
		if($this->league->schedule_type != 'none') {
			$rows[] = array('League SBF:', $this->league->calculate_sbf());
		}

		if($this->league->schedule_type == 'roundrobin') {
			$rows[] = array('Current Round:', $this->league->current_round);
		}

		if($lr_session->has_permission('league','view', $league->league_id, 'delays') ) {
			if( $this->league->email_after )
				$rows[] = array('Scoring reminder delay:', $this->league->email_after . ' hours');
			if( $this->league->finalize_after )
				$rows[] = array('Game finalization delay:', $this->league->finalize_after . ' hours');
		}

		$output .= "<div class='pairtable'>" . table(null, $rows) . "</div>";

		$header = array( 'Team Name', 'Players', 'Rating', 'Avg. Skill', '&nbsp;',);
		if ($this->league->schedule_type == "ratings_ladder" || $this->league->schedule_type == "ratings_wager_ladder" ) {
			array_unshift($header, 'Seed');
		}
		if($lr_session->has_permission('league','manage teams',$this->league->league_id)) {
			$header[] = 'Region';
		}

		$this->league->load_teams();

		if( count($this->league->teams) > 0 ) {
			$rows = array();
			list($order, $season, $round) = $this->league->calculate_standings(array( 'round' => $this->league->current_round ));
			$counter = 0;
			foreach($season as $team) {
				$counter++;
				$team_links = array();
				if($team->status == 'open') {
					$team_links[] = l('join', "team/roster/$team->team_id/" . $lr_session->attr_get('user_id'));
				}
				if($lr_session->has_permission('league','edit',$this->league->league_id)) {
					$team_links[] = l('move', "team/move/$team->team_id");
				}
				if($this->league->league_id == 1 && $lr_session->has_permission('team','delete',$team->team_id)) {
					$team_links[] = l('delete', "team/delete/$team->team_id");
				}

				$row = array();
				if ($this->league->schedule_type == "ratings_ladder"
					|| $this->league->schedule_type == "ratings_wager_ladder" ) {
					$row[] = $counter;
				}

				$row[] = l(display_short_name($team->name, 35), "team/view/$team->team_id");
				$row[] = $team->count_players();
				$row[] = $team->rating;
				$row[] = $team->avg_skill();
				$row[] = theme_links($team_links);
				if($lr_session->has_permission('league','manage teams',$this->league->league_id)) {
					$row[] = $team->region_preference;
				}

				$rows[] = $row;
			}
	
			$output .= "<div class='listtable'>" . table($header, $rows) . "</div>";
		}

		$this->setLocation(array(
			$this->league->fullname => 'league/view/' . $this->league->league_id,
			$this->title => 0));
		return $output;
	}
}

class LeagueDelete extends Handler
{
	function has_permission ()
	{
		global $lr_session;

		if(!$this->league) {
			error_exit("That league does not exist");
		}

		return $lr_session->has_permission('league','delete',$this->league->league_id);
	}

	function process ()
	{
		$this->title = "Delete League";

		$this->setLocation(array(
			$this->team->name => "league/view/" . $this->league->team_id,
			$this->title => 0
		));

		switch($_POST['edit']['step']) {
			case 'perform':
				if ( $this->league->delete() ) {
					local_redirect(url("league/list"));
				} else {
					error_exit("Failure deleting league");
				}
				break;
			case 'confirm':
			default:
				return $this->generateConfirm();
				break;
		}
		error_exit("Error: This code should never be reached.");
	}

	function generateConfirm ()
	{
		global $dbh;
		$rows = array();
		$rows[] = array("League Name:", check_form($this->league->name, ENT_NOQUOTES));
		$output = form_hidden('edit[step]', 'perform');
		$output .= "<p>Do you really wish to delete this league?</p>";
		$output .= "<div class='pairtable'>" . table(null, $rows) . "</div>";
		$output .= form_submit('submit');

		return form($output);
	}
}

// TODO: Common email-list displaying, should take query as argument, return
// formatted list.
class LeagueCaptainEmails extends Handler
{
	var $league;

	function has_permission ()
	{
		global $lr_session;
		return $lr_session->has_permission('league','view',$this->league->league_id, 'captain emails');
	}

	function process ()
	{
		global $dbh;

		$this->title = 'Captain Emails';
		global $lr_session;
	
		$sth = $dbh->prepare(
			"SELECT
				p.firstname, p.lastname, p.email
			FROM
				leagueteams l, teamroster r
				LEFT JOIN person p ON (r.player_id = p.user_id)
			WHERE
				l.league_id = ?
				AND l.team_id = r.team_id
				AND (r.status = 'coach' OR r.status = 'captain' OR r.status = 'assistant')
				AND p.user_id != ?
			ORDER BY
				p.lastname, p.firstname");
	
		$sth->execute(array(
			$this->league->league_id,
			$lr_session->user->user_id));


		$emails = array();
		$names = array();
		while($user = $sth->fetchObject() ) {
			$names[] = "$user->firstname $user->lastname";
			$emails[] = $user->email;
		}

		if( ! count( $emails ) ) {
			error_exit("That league contains no teams.");
		}

		$this->setLocation(array(
			$this->league->fullname => "league/view/" . $this->league->league_id,
			$this->title => 0
		));

		$list = create_rfc2822_address_list($emails, $names, true);
		$output = para("You can cut and paste the emails below into your addressbook, or click " . l('here to send an email', "mailto:$list") . " right away.");

		$output .= pre($list);
		return $output;
	}
}

class LeagueApproveScores extends Handler
{
	function has_permission ()
	{
		global $lr_session;
		return $lr_session->has_permission('league','approve scores',$this->league->league_id);
	}

	function process ()
	{
		global $CONFIG, $dbh;

		$this->title = "Approve Scores";

		$local_adjust_secs = $CONFIG['localization']['tz_adjust'] * 60;

		/* Fetch games in need of verification */
		$game_sth = $dbh->prepare( "SELECT DISTINCT
			se.game_id,
			UNIX_TIMESTAMP(CONCAT(g.game_date,' ',g.game_start)) + ($local_adjust_secs) as timestamp,
			s.home_team,
			h.name AS home_name,
			s.away_team,
			a.name AS away_name
			FROM
				schedule s,
				score_entry se,
				gameslot g,
				team h,
				team a
			WHERE
				s.league_id = ?
				AND (g.game_date < CURDATE()
					OR (
						g.game_date = CURDATE()
						AND g.game_start < CURTIME()
					)
				)
				AND se.game_id = s.game_id
				AND g.game_id = s.game_id
				AND h.team_id = s.home_team
				AND a.team_id = s.away_team
			ORDER BY
				timestamp
		");
		$game_sth->execute( array($this->league->league_id) );

		$header = array(
			'Game Date',
			array('data' => 'Home Team Submission', 'colspan' => 2),
			array('data' => 'Away Team Submission', 'colspan' => 2),
			'&nbsp;'
		);
		$rows = array();

		$se_sth = $dbh->prepare('SELECT score_for, score_against FROM score_entry WHERE team_id = ? AND game_id = ?');
		$captains_sth = $dbh->prepare("SELECT user_id FROM person p
						LEFT JOIN teamroster r ON p.user_id = r.player_id
						WHERE r.team_id IN (?,?) AND r.status IN( 'captain','coach', 'assistant')");

		if( variable_get('narrow_display', '0') )
			$time_format = '%a %b %d %Y, %H%Mh';
		else
			$time_format = '%A %B %d %Y, %H%Mh';

		while($game = $game_sth->fetchObject() ) {
			$rows[] = array(
				array('data' => strftime($time_format, $game->timestamp),'rowspan' => 3),
				array('data' => $game->home_name, 'colspan' => 2),
				array('data' => $game->away_name, 'colspan' => 2),
				array('data' => l("approve score", "game/approve/$game->game_id"))
			);

			$captains_sth->execute(array( $game->home_team, $game->away_team) );
			$emails = array();
			$names = array();
			while($id = $captains_sth->fetchColumn()) {
				$captain = person_load(array('user_id' => $id ));
				$emails[] = $captain->email;
				$names[] = $captain->fullname;
			}

			$se_sth->execute( array( $game->home_team, $game->game_id ) );
			$home = $se_sth->fetch(PDO::FETCH_ASSOC);

			if(!$home) {
				$home = array(
					'score_for' => 'not entered',
					'score_against' => 'not entered',
				);
			}

			$se_sth->execute( array( $game->away_team, $game->game_id ) );
			$away = $se_sth->fetch(PDO::FETCH_ASSOC);
			if(!$away) {
				$away = array(
					'score_for' => 'not entered',
					'score_against' => 'not entered',
				);
			}

			$list = create_rfc2822_address_list($emails, $names, true);
			$rows[] = array(
				"Home Score:", $home['score_for'], "Home Score:", $away['score_against'],
				l('email captains', "mailto:$list")
			);

			$rows[] = array(
				"Away Score:", $home['score_against'], "Away Score:", $away['score_for'], ''
			);

			$rows[] = array( '&nbsp;' );

		}

		$output = para("The following games have not been finalized.");
		$output .= "<div class='listtable'>" . table( $header, $rows ) . "</div>";
		return $output;
	}
}

class LeagueMemberStatus extends Handler
{
	function has_permission()
	{
		global $lr_session;
		return $lr_session->has_permission('league','edit', $this->league->league_id);
	}

	function process ()
	{
		global $lr_session;
		$this->title = "League Member Status";

		$player_id = arg(3);

		if( !$player_id ) {
			$this->setLocation(array( $this->league->fullname => "league/view/" . $this->league->league_id, $this->title => 0));
			$new_handler = new PersonSearch;
			$new_handler->initialize();
			$new_handler->ops['Add to ' . $this->league->fullname] = 'league/member/' .$this->league->league_id . '/%d';
			$new_handler->extra_where = "(class = 'administrator' OR class = 'volunteer')";
			return $new_handler->process();
		}

		if( !$lr_session->is_admin() && $player_id == $lr_session->attr_get('user_id') ) {
			error_exit("You cannot add or remove yourself as league coordinator");
		}

		$player = person_load( array('user_id' => $player_id) );

		switch($_GET['edit']['status']) {
			case 'remove':
				if( ! $this->league->remove_coordinator($player) ) {
					error_exit("Failed attempting to remove coordinator from league");
				}
				break;
			default:
				if($player->class != 'administrator' && $player->class != 'volunteer') {
					error_exit("Only volunteer-class players can be made coordinator");
				}
				if( ! $this->league->add_coordinator($player) ) {
					error_exit("Failed attempting to add coordinator to league");
				}
				break;
		}

		if( ! $this->league->save() ) {
			error_exit("Failed attempting to modify coordinators for league");
		}

		local_redirect(url("league/view/" . $this->league->league_id));
	}
}

class LeagueRatings extends Handler
{
	function has_permission()
	{
		global $lr_session;
		return $lr_session->has_permission('league','ratings', $this->league->league_id);
	}

	function generateForm ( $data = '' ) 
	{
		$output = para("Use the links below to adjust a team's ratings for 'better' or for 'worse'.  Alternatively, you can enter a new rating into the box beside each team then click 'Adjust Ratings' below.  Multiple teams can have the same ratings, and likely will at the start of the season.");
		$output .= para("For the rating values, a <b/>HIGHER</b/> numbered rating is <b/>BETTER</b/>, and a <b/>LOWER</b/> numbered rating is <b/>WORSE</b/>.");
		$output .= para("<b/>WARNING: </b/> Adjusting ratings while the league is already under way is possible, but you'd better know what you are doing!!!");

		$header = array( "Rating", "Team Name", "Avg.<br/>Skill", "New Rating",);
		$rows = array();

		$this->league->load_teams();
		list($order, $season, $round) = $this->league->calculate_standings(array( 'round' => $this->league->current_round ));
		foreach($season as $team) {

			$row = array();
			$row[] = $team->rating;
			$row[] = check_form($team->name);
			$row[] = $team->avg_skill();
			$row[] = "<font size='-4'><a href='#' onClick='document.getElementById(\"ratings_form\").elements[\"edit[$team->team_id]\"].value++; return false'> better </a> " . 
				"<input type='text' size='3' name='edit[$team->team_id]' value='$team->rating' />" .
				"<a href='#' onClick='document.getElementById(\"ratings_form\").elements[\"edit[$team->team_id]\"].value--; return false'> worse</a></font>";

			$rows[] = $row;
		}
		$output .= "<div class='listtable'>" . table($header, $rows) . "</div>";
		$output .= form_hidden("edit[step]", 'perform');
		$output .= "<input type='reset' />&nbsp;<input type='submit' value='Adjust Ratings' /></div>";

		return form($output, 'post', null, 'id="ratings_form"');
	}

	function process ()
	{
		$this->title = "League Ratings Adjustment";

		$edit = &$_POST['edit'];

		switch($edit['step']) {
			case 'perform':
				$this->perform($edit);
				local_redirect(url("league/view/" . $this->league->league_id));
				break;
			default:
				$rc = $this->generateForm();
		}
		$this->setLocation(array( $this->league->name => "league/view/" . $this->league->league_id, $this->title => 0));

		return $rc;

	}

	function perform ( $edit )
	{
		global $dbh;
		// make sure the teams are loaded
		$this->league->load_teams();

		$sth = $dbh->prepare('UPDATE team SET rating = ? WHERE team_id = ?');	
		// go through what was submitted
		foreach ($edit as $team_id => $rating) {
			if (is_numeric($team_id) && is_numeric($rating)) {
				$team = $this->league->teams[$team_id];

				// TODO:  Move this logic to a function inside the league.inc file
				// update the database
				$sth->execute( array( $rating, $team_id ) );
			}
		}

		return true;
	}
}

class LeagueSpirit extends Handler
{
	function has_permission ()
	{
		global $lr_session;
		return $lr_session->has_permission('league','view', $this->league->league_id, 'spirit');
	}

	function process ()
	{
		global $dbh, $CONFIG;
		$this->title = "League Spirit";

		$this->setLocation(array(
			$this->league->fullname => "league/spirit/". $this->league->league_id,
			$this->title => 0));

		/*
		 * Grab schedule info
		 */
		$games = game_load_many( array( 'league_id' => $this->league->league_id, '_order' => 'g.game_date,g.game_id') );

		if( !is_array($games) ) {
			error_exit("There are no games scheduled for this league");
		}

		$s = new Spirit;
		$s->entry_type = $this->league->enter_sotg;
		$s->display_numeric_sotg = $this->league->display_numeric_sotg();

		/*
		 * Show overall league spirit
		 */
		$rows   = $s->league_sotg( $this->league );
		$rows[] = $s->league_sotg_averages( $this->league );
		$rows[] = $s->league_sotg_std_dev( $this->league );
		$output = h2('Team spirit summary')
			. table(
				array_merge(
					array(
						'Team',
						'Average',
					),
					(array)$s->question_headings()
				),
				$rows,
				array('alternate-colours' => true)
			);

		$output .= h2('Distribution of team average spirit scores')
			. table(
				array(
					'Spirit score',
					'Number of teams',
					'Percentage of league'
				),
				$s->league_sotg_distribution( $this->league )
			)
			. "\n";


		/*
		 * Show every game
		 */
		$header = array_merge(
			array(
				'Game',
				'Entry By',
				'Given To',
				'Score',
			),
			(array)$s->question_headings()
		);
		$rows = array();
		$question_column_count = count($s->question_headings());
		while(list(,$game) = each($games)) {

			$teams = array(
				$game->home_team => $game->home_name,
				$game->away_team => $game->away_name
			);
			while( list($giver,$giver_name) = each ($teams)) {

				$recipient = $game->get_opponent_id ($giver);

				$thisrow = array(
					l($game->game_id, "game/view/$game->game_id")
						. " " .  strftime('%a %b %d %Y', $game->timestamp),
					l($giver_name, "team/view/$giver"),
					l($teams[$recipient], "team/view/$recipient")
				);

				# Fetch spirit answers for games
				$entry = $game->get_spirit_entry( $recipient );
				if( !$entry ) {
					$thisrow[] = array(
						'data'    => 'Team did not submit a spirit rating',
						'colspan' => $question_column_count + 1,
					);
					$rows[] = $thisrow;
					continue;
				}

				$thisrow = array_merge(
					$thisrow,
					(array)$s->render_game_spirit( $entry )
				);

				$rows[] = $thisrow;
				if( $entry['comments'] != '' ) {
					$rows[] = array(
						array(
							'colspan' => 2,
							'data' => '<b>Comment for entry above:</b>'
						),
						array(
							'colspan' => count($header) - 2,
							'data'    => $entry['comments'],
						)
					);
				}
			}
		}

		$style = '#main table td { font-size: 80% }';
		if( variable_get('narrow_display', '0') ) {
			$style .= ' th { font-size: 70%; }';
		}
		$output .= h2('Spirit reports per game');
		$output .= "<style>$style</style>" . table($header,$rows, array('alternate-colours' => true) );

		return $output;
	}
}

/**
 * Download a CSV spirit report for all games played
 */
class LeagueSpiritDownload extends Handler
{
	function has_permission()
	{
		global $lr_session;
		return $lr_session->has_permission('league', 'download', $this->league->league_id, 'spirit');
	}

	function process ()
	{
		global $dbh;

		$games = game_load_many( array( 'league_id' => $this->league->league_id, '_order' => 'g.game_date,g.game_id') );

		if( !is_array($games) ) {
			error_exit("There are no games scheduled for this league");
		}

		$s = new Spirit;
		$s->entry_type = $this->league->enter_sotg;
		$s->display_numeric_sotg = $this->league->display_numeric_sotg();

		// Start the output, let the browser know what type it is
		header('Content-type: text/x-csv');
		header("Content-Disposition: attachment; filename=\"spirit{$this->league_id}.csv\"");
		$out = fopen('php://output', 'w');

		$header = array_merge(
			array(
				'Game #',
				'Date',
				'Giver Name',
				'Giver ID',
				'Given To',
				'Given To ID',
				'SOTG Total',
			),
			(array)$s->question_headings(),
			array(
				'Comments',
			)
		);
		fputcsv($out, $header);

		while(list(,$game) = each($games)) {

			$teams = array(
				$game->home_team => $game->home_name,
				$game->away_team => $game->away_name
			);
			while( list($giver,$giver_name) = each ($teams)) {

				$recipient = $game->get_opponent_id ($giver);

				# Fetch spirit answers for games
				$entry = $game->get_spirit_entry( $recipient );
				if( !$entry ) {
					$entry = array(
						comments => 'Team did not submit a spirit rating',
					);
				} else {
					if( ! $entry['entered_sotg'] ) {
						$entry['entered_sotg'] = (
							$entry['timeliness'] + $entry['rules_knowledge'] + $entry['sportsmanship'] + $entry['rating_overall'] + $entry['score_entry_penalty']
						);
					}
				}

				$thisrow = array(
					$game->game_id,
					strftime('%a %b %d %Y', $game->timestamp),
					$giver_name,
					$giver,
					$teams[$recipient],
					$recipient,
					$entry['entered_sotg'],
					$entry['timeliness'],
					$entry['rules_knowledge'],
					$entry['sportsmanship'],
					$entry['rating_overall'],
					$entry['score_entry_penalty'],
					$entry['comments'],
				);

				fputcsv($out, $thisrow);
			}
		}
		fclose($out);

		// Returning would cause the Leaguerunner menus to be added
		exit;
	}
}

class LeagueStatusReport extends Handler
{
	function has_permission()
	{
		global $lr_session;
		return $lr_session->has_permission('league','edit', $this->league->league_id);
	}

	function process ()
	{
		$this->title = "League Status Report";

		$rc = $this->generateStatusPage();

		$this->setLocation(array( $this->league->name => "league/status/" . $this->league->league_id, $this->title => 0));

		return $rc;
	}

	function generateStatusPage ( )
	{
		// make sure the teams are loaded
		$this->league->load_teams();

		list($order, $season, $round) = $this->league->calculate_standings(array( 'round' => $this->league->current_round ));

		$fields = array();
		$sth = field_query( array( '_extra' => '1 = 1', '_order' => 'f.code') );
		while( $field = $sth->fetchObject('Field') ) {
			$fields[$field->code] = $field->region;
		}

		$output = para("This is a general scheduling status report for rating ladder leagues.");

		$header[] = array('data' => "Rating", 'rowspan' => 2);
		$header[] = array('data' => "Team", 'rowspan' => 2);
		$header[] = array('data' => "Games", 'rowspan' => 2);
		$header[] = array('data' => "Home/Away", 'rowspan' => 2);
		$header[] = array('data' => "Region", 'colspan' => 4);
		$header[] = array('data' => "Region Pct", 'rowspan' => 2);
		$header[] = array('data' => "Opponents", 'rowspan' => 2);
		$header[] = array('data' => "Repeat Opponents", 'rowspan' => 2);

		$subheader[] = array('data' => "C", 'class' => "subtitle");
		$subheader[] = array('data' => "E", 'class' => "subtitle");
		$subheader[] = array('data' => "S", 'class' => "subtitle");
		$subheader[] = array('data' => "W", 'class' => "subtitle");

		$rows = array();
		$rows[] = $subheader;

		$rowstyle = "standings_light";

		// get the schedule
		$schedule = array();
		$sth = game_query ( array( 'league_id' => $this->league->league_id, '_order' => 'g.game_date, g.game_start, field_code') );
		while($g = $sth->fetchObject('Game') ) {
			$schedule[] = $g;
		}

		while(list(, $tid) = each($order)) {
			if ($rowstyle == "standings_light") {
				$rowstyle = "standings_dark";
			} else {
				$rowstyle = "standings_light";
			}
			$row = array( array('data'=>$season[$tid]->rating, 'class'=>"$rowstyle") );
			$row[] = array('data'=>l($season[$tid]->name, "team/view/$tid"), 'class'=>"$rowstyle");

			// count number of games for this team:
			//$games = game_load_many( array( 'either_team' => $this->team->team_id, '_order' => 'g.game_date,g.game_id') );
			$numgames = 0;
			$homegames = 0;
			$awaygames = 0;

			$region = array(
				'Central' => 0,
				'East' => 0,
				'South' => 0,
				'West' => 0,
			);

			$opponents = array();

			// parse the schedule
			reset($schedule);
			while(list(,$game) = each($schedule)) {
				if ($game->home_team == $tid) {
					$numgames++;
					$homegames++;
					$opponents[$game->away_team]++;
				}
				if ($game->away_team == $tid) {
					$numgames++;
					$awaygames++;
					$opponents[$game->home_team]++;
				}
				if ($game->home_team == $tid || $game->away_team == $tid) {
					list($code, $num) = split(" ", $game->field_code);
					$region[$fields[$code]]++;
				}
			}
			//reset($games);

			$row[] = array('data'=>$numgames, 'class'=>"$rowstyle", 'align'=>"center");
			$row[] = array('data'=> _ratio_helper( $homegames, $numgames), 'class'=>$rowstyle, 'align'=>"center");

			// regions:
			$pref = '---';
			$region_count = 0;
			if ($season[$tid]->region_preference != "---" && $season[$tid]->region_preference != "") {
				$pref = $season[$tid]->region_preference;
				$region_count  = $region[$pref];
				$region[$pref] = "<b><font color='blue'>$region_count</font></b>";
			} else {
				// No region preference means they're always happy :)
				$region_count = $numgames;
			}
			$row[] = array('data'=>$region['Central'], 'class'=>"$rowstyle");
			$row[] = array('data'=>$region['East'], 'class'=>"$rowstyle");
			$row[] = array('data'=>$region['South'], 'class'=>"$rowstyle");
			$row[] = array('data'=>$region['West'], 'class'=>"$rowstyle");
			$row[] = array('data'=> _ratio_helper( $region_count, $numgames), 'class' => $rowstyle);

			$row[] = array('data'=>count($opponents), 'class'=>"$rowstyle", 'align'=>"center");

			// figure out the opponent repeats
			$opponent_repeats="";
			while(list($oid, $repeats) = each($opponents)) {
				if ($repeats > 2) {
					$opponent_repeats .= $season[$oid]->name . " (<font color='red'><b>$repeats</b></font>) <br>";
				} else if ($repeats > 1) {
					$opponent_repeats .= $season[$oid]->name . " (<b>$repeats</b>) <br>";
				}
			}
			$row[] = array('data'=>$opponent_repeats, 'class'=>"$rowstyle");

			$rows[] = $row;
		}

		//$output .= table($header, $rows);
		$output .= "<div class='listtable'>" . table($header, $rows) . "</div>";

		return form($output);
	}
}


// RK: print a field distribution report for this league
// for field balancing
class LeagueFieldReport extends Handler
{
	function has_permission()
	{
		global $lr_session;
		return $lr_session->has_permission('league','edit', $this->league->league_id);
	}

	function process ()
	{
		$this->title = "League Field Distribution Report";

		$rc = $this->generateStatusPage();

		$this->setLocation(array( $this->league->name => "league/fields/" . $this->league->league_id, $this->title => 0));

		return $rc;
	}

	function generateStatusPage ( )
	{
		global $dbh;

		// make sure the teams are loaded
		$this->league->load_teams();

		list($order, $season, $round) = $this->league->calculate_standings(array( 'round' => $this->league->current_round ));

		$fields = array();
		$sth = field_query( array( '_order' => 'f.code') );
		while( $field = $sth->fetchObject('Field') ) {
			$fields[$field->code] = $field->region;
		}

		$output = para("This is a general field scheduling balance report for the league.");

		$num_teams = sizeof($order);

		$header[] = array('data' => "Rating", 'rowspan' => 2);
		$header[] = array('data' => "Team", 'rowspan' => 2);
		$header[] = array('data' => "Region", 'rowspan' => 2);

		// now gather all possible fields this league can use
		$sth = $dbh->prepare('SELECT
				DISTINCT IF(f.parent_fid, pf.code, f.code) AS field_code,
				TIME_FORMAT(g.game_start, "%H:%i") as game_start,
				IF(f.parent_fid, pf.region, f.region) AS field_region,
				IF(f.parent_fid, pf.fid, f.fid) AS fid,
				IF(f.parent_fid, pf.name, f.name) AS name
			FROM league_gameslot_availability a
			INNER JOIN gameslot g ON (g.slot_id = a.slot_id)
			LEFT JOIN field f ON (f.fid = g.fid)
			LEFT JOIN field pf ON (pf.fid = f.parent_fid)
			WHERE a.league_id = ?
			ORDER BY field_region DESC, field_code, game_start');
		$sth->execute( array ($this->league->league_id) );
		$last_region = "";
		$field_region_count = 0;
		while($row = $sth->fetch(PDO::FETCH_OBJ)) {
			$field_list[] = "$row->field_code $row->game_start";
			$subheader[] = array('data' => l($row->field_code, "field/view/$row->fid",
							 array('title'=> $row->name)) . " $row->game_start",
					     'class' => "subtitle");
			if ($last_region == $row->field_region) {
				$field_region_count++;
			} else {
				if ($field_region_count > 0) {
					$header[] = array('data' => $last_region,
							  'colspan' => $field_region_count);
				}
				$last_region = $row->field_region;
				$field_region_count = 1;
			}
		}
		// and make the last region header too
		if ($field_region_count > 0) {
			$header[] = array('data' => $last_region,
					  'colspan' => $field_region_count);
		}
		$header[] = array('data' => "Games", 'rowspan' => 2);

		$rows = array();
		$rows[] = $subheader;

		$rowstyle = "standings_light";

		// get the schedule
		$schedule = array();
		$sth = game_query ( array( 'league_id' => $this->league->league_id, '_order' => 'g.game_date, g.game_start, field_code') );
		while($g = $sth->fetchObject('Game') ) {
			$schedule[] = $g;
		}

		// we'll cache these results, so we can compute avgs and highlight numbers too far from average
		$cache_rows = array();
		while(list(, $tid) = each($order)) {
			if ($rowstyle == "standings_light") {
				$rowstyle = "standings_dark";
			} else {
				$rowstyle = "standings_light";
			}
			$row = array( array('data'=>$season[$tid]->rating, 'class'=>"$rowstyle") );
			$row[] = array('data'=>l($season[$tid]->name, "team/view/$tid"), 'class'=>"$rowstyle");
			$row[] = array('data'=>$season[$tid]->region_preference, 'class'=>"$rowstyle");

			// count number of games per field for this team:
			$numgames = 0;
			$count = array();

			// parse the schedule
			reset($schedule);
			while(list(,$game) = each($schedule)) {
				if ($game->home_team == $tid || $game->away_team == $tid) {
					$numgames++;
					list($code, $num) = split(" ", $game->field_code);
					$count["$code $game->game_start"]++;
				}
			}

			foreach ($field_list as $f) {
				if ($count[$f]) {
					$row[] = array('data'=> $count[$f], 'class'=>"$rowstyle", 'align'=>'center');
					$total_at_field[$f] += $count[$f];
				} else {
					$row[] = array('data'=> "0", 'class'=>"$rowstyle", 'align'=>'center');
				}
			}

			$row[] = array('data'=>$numgames, 'class'=>"$rowstyle", 'align'=>"center");

			$cache_rows[] = $row;
		}

		// pass through cached rows and highlight entries far from avg
		foreach ($cache_rows as $row) {
			$i = 3;  // first data column
			foreach ($field_list as $f) {
				$avg = $total_at_field[$f] / $num_teams;
				// we'll consider more than 1.5 game from avg too much
				if ($avg - 1.5 > $row[$i]['data'] || $row[$i]['data'] > $avg + 1.5) {
					$row[$i]['data'] = "<b><font color='red'>". ($row[$i]['data']) ."</font></b>";
				}
				$i++; // move to next column in cached row
			}
			$rows[] = $row;
		}

		// output totals line
		$row = array(array('data' => "Total games:", 'colspan' => 3, 'align' => 'right'));
		foreach ($field_list as $f) {
			if ($total_at_field[$f]) {
				$row[] = array('data'=> $total_at_field[$f], 'align'=>'center');
			} else {
				$row[] = array('data'=> "0", 'align'=>'center');
			}
		}
		$rows[] = $row;

		$row = array(array('data' => "Average:", 'colspan' => 3, 'align' => 'right'));
		foreach ($field_list as $f) {
			if ($total_at_field[$f]) {
				$row[] = array('data'=> sprintf('%.1f', $total_at_field[$f] / $num_teams), 'align'=>'center');
			} else {
				$row[] = array('data'=> "0", 'align'=>'center');
			}
		}
		$rows[] = $row;


		//$output .= table($header, $rows);
		$output .= "<div class='listtable'>" . table($header, $rows) . "</div>";

		return form($output);
	}
}

/*
 * RK: tabular report of scores for all league games
 */
class LeagueScoresTable extends Handler
{
	function has_permission ()
	{
		global $lr_session;
		return $lr_session->has_permission('league','view', $this->league->league_id);
	}

	function process ()
	{
		$id = arg(2);

		$this->title = "Scores";

		if($this->league->schedule_type == 'none') {
			error_exit("This league does not have a schedule or standings.");
		}

		// TODO: do we need to handle multiple rounds differently?

		$this->setLocation(array(
			$this->league->fullname => "league/scores/$id",
			$this->title => 0,
		));

		list($order, $season, $round) = $this->league->calculate_standings(array( 'round' => $current_round ));

		$this->league->load_teams();
		if( $this->league->teams <= 0 ) {
			return para('This league has no teams.');
		}

		$header = array('');
		$seed = 0;
		foreach ($order as $tid) {
			$seed++;
			$short_name = $season[$tid]->name;
			$header[] = l($short_name, "team/view/$tid",
						  array('title' => htmlspecialchars($season[$tid]->name)
								." Rank:$seed Rating:".$season[$tid]->rating));
		}
		$header[] = '';

		$rows = array($header);

		$seed = 0;
		foreach ($order as $tid) {
			$seed++;
			$row = array();
			$row[] = l($season[$tid]->name, "team/schedule/$tid", 
					   array('title'=>"Rank:$seed Rating:".$season[$tid]->rating));

			// grab schedule information
			$games = game_load_many( array( 'either_team' => $tid,
											'_order' => 'g.game_date,g.game_start,g.game_id') );
			$gameentry = array();
			//while(list(,$game) = each($games)) {
			foreach ($games as &$game) {
				if($game->home_id == $tid) {
					$opponent_id = $game->away_id;
				} else {
					$opponent_id = $game->home_id;
				}
				// if score finalized, save game for printing
				if($game->is_finalized()) {
					$gameentry[$opponent_id][] = $game;
				}
			}

			// output game results row
			foreach ($order as $opponent_id) {
				if ($opponent_id == $tid) {
					// no games against my own team
					$row[] = array('data'=>'&nbsp;', 'bgcolor'=>'gray');
					continue;
				}
				if( ! array_key_exists($opponent_id, $gameentry) ) {
					// no games against this team
					$row[] = array('data'=>'&nbsp;');
					continue;
				}

				$results = array();
				$wins = $losses = 0;
				foreach ($gameentry[$opponent_id] as &$game) {
					$game_score = '';
					$game_result = "";
					switch($game->status) {
					case 'home_default':
						$game_score = "(default)";
						$game_result = "$game->home_name defaulted";
						break;
					case 'away_default':
						$game_score = "(default)";
						$game_result = "$game->away_name defaulted";
						break;
					case 'forfeit':
						$game_score = "(forfeit)";
						$game_result = "forfeit";
						break;
					default: //normal finalized game
						if($game->home_id == $tid) {
							$opponent_name = $game->away_name;
							$game_score = "$game->home_score-$game->away_score";
							if ($game->home_score > $game->away_score) {
								$wins++;
							} else if ($game->home_score < $game->away_score) {
								$losses++;
							}
						} else {
							$opponent_name = $game->home_name;
							$game_score = "$game->away_score-$game->home_score";
							if ($game->away_score > $game->home_score) {
								$wins++;
							} else if ($game->away_score < $game->home_score) {
								$losses++;
							}
						}
						if ($game->home_score > $game->away_score) {
							$game_result = "$game->home_name defeated $game->away_name"
								." $game->home_score-$game->away_score";
						} else if ($game->home_score < $game->away_score) {
							$game_result = "$game->away_name defeated $game->home_name"
								." $game->away_score-$game->home_score";
						} else {
							$game_result = "$game->home_name and $game->away_name tied $game_score";
						}
						$game_result .= " ($game->rating_points rating points transferred)";
					}

					$popup = strftime('%a %b %d', $game->timestamp)." at $game->field_code: $game_result";

					$results[] = l($game_score, "game/view/$game->game_id",
								   array('title' => htmlspecialchars($popup)));
				}
				$thiscell = implode('<br />', $results);
				if ($thiscell == '') {
					$thiscell = '&nbsp;';
				}
				if ($wins > $losses) {
					/* $row[] = array('data'=>$thiscell, 'bgcolor'=>'#A0FFA0'); */
					$row[] = array('data'=>$thiscell, 'class'=>'winning');
				} else if ($wins < $losses) {
					$row[] = array('data'=>$thiscell, 'class'=>'losing');
				} else {
					$row[] = $thiscell;
				}
			}

			// repeat team name
			$row[] = l($season[$tid]->name, "team/schedule/$tid", 
					   array('title'=>"Rank:$seed Rating:".$season[$tid]->rating));
			$rows[] = $row;
		}

		//return "<div class='pairtable'>" . table(null, $rows, array('border'=>'1')) . "</div>"
		return "<div class='scoretable'>" . table(null, $rows, array('class'=>'scoretable')) . "</div>"
			. para("Scores are listed with the first score belonging the team whose name appears on the left.<br />"
			. "Green backgrounds means row team is winning season series, red means column team is winning series. Defaulted games are not counted.");
		//return "<div class='pairtable'>" . table(null, $rows, array('style'=>'border: 1px solid gray;')) . "</div>";
		//return "<div class='listtable'>" . table(null, $rows) . "</div>";
	}
}

/*
 * RK: report of which fields are available for use
 */
class LeagueFieldAvailability extends Handler
{
	function has_permission()
	{
		global $lr_session;
		return $lr_session->has_permission('league','edit', $this->league->league_id);
	}

	function process ()
	{
		$this->title = 'League Field Availability Report';

		$this->setLocation(array(
			$this->league->fullname => 'league/slots/'.$this->league->league_id,
			$this->title => 0,
		));

		$today = getdate();

		$year  = arg(3);
		$month = arg(4);
		$day   = arg(5);

		if(! validate_number($month)) {
			$month = $today['mon'];
		}

		if(! validate_number($year)) {
			$year = $today['year'];
		}
		if( $day ) {
			if( !validate_date_input($year, $month, $day) ) {
				return 'That date is not valid';
			}
			$formattedDay = strftime('%A %B %d %Y', mktime (6,0,0,$month,$day,$year));
			$this->setLocation(array(
				"$this->title &raquo; $formattedDay" => 0));
			return $this->displaySlotsForDay( $year, $month, $day );
		} else {
			$this->setLocation(array( "$this->title" => 0));
			$output = para('Select a date below on which to view all available gameslots');
			$output .= generateCalendar( $year, $month, $day,
										 'league/slots/'.$this->league->league_id, 
										 'league/slots/'.$this->league->league_id);
			return $output;
		}
	}

	/**
	 * List all games on a given day.
	 */
	function displaySlotsForDay ( $year, $month, $day )
	{
		global $dbh;

		menu_add_child($this->league->fullname."/slots", "$league->fullname/slots/$year/$month/$day","$year/$month/$day", array('weight' => 1, 'link' => "league/slots/".$this->league->league_id."/$year/$month/$day"));

		$rows = array(
			array(
				array('data' => strftime('%a %b %d %Y',mktime(6,0,0,$month,$day,$year)), 'colspan' => 7, 'class' => 'gamedate')
			),
        		array(
				 array('data' => 'Slot', 'class' => 'column-heading'),
				 array('data' => 'Field', 'class' => 'column-heading'),
				 array('data' => 'Game', 'class' => 'column-heading'),
				 array('data' => 'Home', 'class' => 'column-heading'),
				 array('data' => 'Away', 'class' => 'column-heading'),
				 array('data' => 'Field Region', 'class' => 'column-heading'),
				 array('data' => 'Home Pref', 'class' => 'column-heading'),
			 )
		);

		$sth = $dbh->prepare('SELECT
			g.slot_id,
			COALESCE(f.code, pf.code) AS field_code,
			COALESCE(f.num, pf.num)   AS field_num,
			COALESCE(f.region, pf.region) AS field_region,
			g.fid,
			t.region_preference AS home_region_preference,
			IF(g.fid = t.home_field,
				1,
				COALESCE(f.region,pf.region) = t.region_preference) AS is_preferred,
			g.game_id

		FROM
			league_gameslot_availability l,
			gameslot g
				LEFT JOIN schedule s ON (g.game_id = s.game_id)
				LEFT JOIN team t ON (s.home_team = t.team_id),
			field f LEFT JOIN field pf ON (f.parent_fid = pf.fid)
		WHERE l.league_id = ?
			AND g.game_date = ?
			AND g.slot_id = l.slot_id
			AND f.fid = g.fid
			ORDER BY field_code, field_num');
		$sth->execute( array ($this->league->league_id,
				sprintf('%d-%d-%d', $year, $month, $day)) );

		$num_open = 0;
		while($g = $sth->fetch()) {

			$row = array(
				$g['slot_id'],
				l($g['field_code'] . $g['field_num'], "field/view/" . $g['fid'])
			);

			// load game info, if game scheduled
			if ($g['game_id']) {
				$game = game_load( array('game_id' => $g['game_id']) );
				$sched = schedule_render_viewable($game);
				$row[] = l($g['game_id'], "game/view/".$g['game_id']);
				$row[] = $sched[3];
				$row[] = $sched[5];

				$color = 'white';
				if( ! $g['is_preferred'] && ($g['home_region_preference'] && $g['home_region_preference'] != '---') ) {
					/* Show in red if it's an unsatisfied preference */
					$color = 'red';
				}
				$row[] = array( 'data' => $g['field_region'], 'style' => "background-color: $color");
				$row[] = array( 'data' => $g['home_region_preference'], 'style' => "background-color: $color");
			} else {
				$row[] = array('data' => "<b>---- field open ----</b>",
							   'colspan' => '3');
				$row[] = $g['field_region'];
				$row[] = '&nbsp;';
				$num_open++;
			}

			$rows[] = $row;
		}
		if( ! count( $rows ) ) {
			error_exit("No gameslots available for this league on this day");
		}
		$num_fields = count($rows);

		$output .= "<div class='schedule'>" . table(null, $rows) . "</div>"
			. para("There are $num_fields fields available for use this week, currently $num_open of these are unused.");
		return $output;
	}

}

function _ratio_helper( $count, $total )
{
	$ratio = 0;
	if( $total > 0 ) {
		$ratio = $count / $total;
	}
	$output = sprintf("%.3f (%d/%d)", $ratio, $count, $total);

	// For odd numbers of games, don't warn if we're just under an
	// impossible-to-reach 50%.
	$check_ratio = $ratio;
	if( $total % 2 ) {
		$check_ratio = ($count+1)/($total+1);
	}

	if( $check_ratio < 0.5 ) {
		$output = "<font color='red'><b>$output</b></font>";
	}
	return $output;
}

/**
 * Sort the rows of the teams array by the second element (SOTG score)
 */
function team_sotg_cmp($a, $b)
{
	if ($a[1] == $b[1]) {
		return 0;
	}
	return ($a[1] > $b[1]) ? -1 : 1;
}

?>
