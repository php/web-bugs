<?php
response_header('Add Pull Request :: ' . clean($package_name));
?>
<script type='text/javascript' src='https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js'></script>
<script type="text/javascript" src="js/Markdown.Converter.js"></script>
<script type="text/javascript" src="js/Markdown.Sanitizer.js"></script>
<h2>Add a Pull Request to <a href="bug.php?id=<?php echo $bug_id; ?>">Bug #<?php echo $bug_id; ?></a></h2>
<ul>
 <li>One problem per pull request, please</li>
 <li>The pull requst must be opened against a PHP project on GitHub</li>
 <li>Choose a meaningful request name (i.e. include bug id and title)</li>
</ul>
<form name="patchform" method="post" action="gh-pull-add.php" enctype="multipart/form-data">
<input type="hidden" name="MAX_FILE_SIZE" value="102400" />
<input type="hidden" name="bug" value="<?php echo $bug_id; ?>" />
<?php
if (!empty($errors)) {
    foreach ($errors as $err) {
        echo '<div class="errors">' . htmlspecialchars($err) . '</div>';
    }
}
?>
<table>
<?php
if (!$logged_in) {
	$captcha = $numeralCaptcha->getOperation();
	$_SESSION['answer'] = $numeralCaptcha->getAnswer();
?>
 <tr>
  <th class="form-label_left">
   Email Address (MUST BE VALID)
  </th>
  <td class="form-input">
   <input type="text" name="email" value="<?php echo clean($email); ?>" />
  </td>
 </tr>
 <tr>
  <th>Solve the problem:<br /><?php echo $captcha; ?> = ?</th>
  <td class="form-input"><input type="text" name="captcha" /></td>
 </tr>
<?php } ?>
 <tr>
  <th class="form-label_left">
   Repository:
  </th>
  <td class="form-input">
   <select name="repository" id="repository_field"><option value=""></option></select>
   <small>The repository must belong to http://github.com/php/.</small>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Pull Request:
  </th>
  <td class="form-input">
   <select name="pull_id" id="pull_id_field"></select>
   <div id="pull_details"></div>
  </td>
 </tr>
</table>
<br />
<input type="submit" name="addpull" value="Save" />
</form>
<script>
var gh_pulls = false;
var converter;
$(document).ready(function() {
  var org = "php";
  var baseurl = "https://api.github.com/";
  var url = baseurl+'orgs/'+org+'/repos';
  converter = new Markdown.getSanitizingConverter();
  $.ajax({ dataType: 'jsonp', url: url, success: function(d) {
    var repos = new Array();
    for (var i in d.data) {
      repos.push(d.data[i].name);
    }
    repos.sort();
    for (var i in repos) {
      $("#repository_field").append("<option>"+repos[i]+"</option>");
    }
  } });
});

$("#repository_field").change(function() {
  $("#pull_id_field").empty();
  gh_pulls = false;
  $("#pull_id_field").append("<option value=''></option>");
  var repo = $("#repository_field").val();
  if (repo == "") {
    return;
  }
  var org = "php";
  var baseurl = "https://api.github.com/";
  var url = baseurl+'repos/'+org+'/'+repo+'/pulls?per_page=100';
  $.ajax({ dataType: 'jsonp', url: url, success: function(d) {
    d.data.sort(function(a, b) { return a.number - b.number; });
    for (var i in d.data) {
      $("#pull_id_field").append("<option value="+(d.data[i].number+0)+">"+d.data[i].number+" - "+d.data[i].title+"</option>");
    }
    gh_pulls = d.data;
  }});
});

$("#pull_id_field").change(function() {
  var val = $("#pull_id_field").val();
  $('#pull_details').empty();
  if (val == "" || !gh_pulls) {
    return;
  }
  var pr = false;
  for (var i in gh_pulls) {
    if (gh_pulls[i].number == val) {
      pr = gh_pulls[i];
      break;
    }
  }
  if (pr) {
    $('#pull_details').append('<b>'+pr.title+'</b><br>'+converter.makeHtml(pr.body));
  }
});
</script>
<?php if (!empty($patches)) { ?>
<h2>Existing patches: TODO</h2>
<?php
}

$canpatch = false;
require "{$ROOT_DIR}/templates/listpulls.php";
response_footer();
