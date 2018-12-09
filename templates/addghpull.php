<?php
response_header('Add Pull Request :: ' . clean($package_name));
?>
<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js'></script>
<script src="js/Markdown.Converter.js"></script>
<script src="js/Markdown.Sanitizer.js"></script>
<h2>Add a Pull Request to <a href="bug.php?id=<?php echo $bug_id; ?>">Bug #<?php echo $bug_id; ?></a></h2>
<ul>
 <li>One problem per pull request, please</li>
 <li>The pull request must be opened against a PHP project on GitHub</li>
 <li>Choose a meaningful request name (i.e. include bug id and title)</li>
</ul>
<form name="patchform" method="post" action="gh-pull-add.php">
<input type="hidden" name="bug" value="<?php echo $bug_id; ?>">
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
	$_SESSION['answer'] = $captcha->getAnswer();
?>
 <tr>
  <th class="form-label_left">
   Email Address (MUST BE VALID)
  </th>
  <td class="form-input">
   <input type="text" name="email" value="<?php echo clean($email); ?>">
  </td>
 </tr>
 <tr>
  <th>Solve the problem:<br><?php echo $captcha->getQuestion(); ?></th>
  <td class="form-input"><input type="text" name="captcha"></td>
 </tr>
<?php } ?>
 <tr>
  <th class="form-label_left">
   Repository:
  </th>
  <td class="form-input">
   <select name="repository" id="repository_field"><option value=""></option></select>
   <small>The repository must belong to https://github.com/php</small>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Pull Request:
  </th>
  <td class="form-input">
   <img src="images/loading-blue.gif" id="loading">
   <select name="pull_id" id="pull_id_field"></select>
   <div id="pull_details"></div>
  </td>
 </tr>
</table>
<br>
<input type="submit" name="addpull" value="Save">
</form>
<script>
var gh_pulls = false;
var converter;
if (typeof($) != "function") {
  window.alert("Failed to load jQuery!");
}

$(document).ready(function() {
  var MAX_PER_PAGE = 100;
  var org = "php";
  var baseurl = "https://api.github.com/";
  var url = baseurl+'orgs/'+org+'/repos?per_page=' + MAX_PER_PAGE;

  converter = new Markdown.getSanitizingConverter();

  $("#pull_id_field").empty().hide();
  $('#pull_details').empty();


  // using https://gist.github.com/niallo/3109252
  function parse_link_header(header) {
    if (header.length === 0) {
      throw new Error("input must not be of zero length");
    }

    // Split parts by comma
    var parts = header.split(',');
    var links = {};
    // Parse each part into a named link
    for(var i=0; i<parts.length; i++) {
      var section = parts[i].split(';');
      if (section.length !== 2) {
        throw new Error("section could not be split on ';'");
      }
      var url = section[0].replace(/<(.*)>/, '$1').trim();
      var name = section[1].replace(/rel="(.*)"/, '$1').trim();
      links[name] = url;
    }
    return links;
  }


  function recursiveFetch(url, items, finalCallback) {
    var hasNext = false;
    $.ajax({ dataType: 'json', url: url, success: function(data, textStatus, request) {
      items = items.concat(data);
      if (request.getResponseHeader('Link')) {
        var links = parse_link_header(request.getResponseHeader('Link'));
        if (links['next']) {
          hasNext = true;
          recursiveFetch(links['next'], items, finalCallback);
        }
      }
      if (hasNext === false) {
        finalCallback(items);
      }
    }
    });
  }

  var repos = [], gh_pulls = [];
  recursiveFetch(url, [], function(items) {
    items.map(function(repo) {
      repos.push(repo.name);
    });
    repos.sort();
    repos.map(function(repo) {
      $("#repository_field").append("<option>"+repo+"</option>");
    });
    $("#loading").hide();
  });

  $("#repository_field").change(function() {
    $("#pull_id_field").empty().hide();
    $('#pull_details').empty();
    $('#loading').show();
    gh_pulls = [];
    $("#pull_id_field").append("<option value=''></option>");
    var repo = $("#repository_field").val();
    if (repo == "") {
      $('#loading').hide();
      return;
    }

    var url = baseurl + 'repos/' + org + '/' + repo + '/pulls?per_page=' + MAX_PER_PAGE;
    recursiveFetch(url, [], function(items) {
      items.map(function(item) {
        $("#pull_id_field").append("<option value=" + (item.number + 0) + ">" + item.number + " - " + item.title + "</option>");
      });
      gh_pulls = items;
      $("#pull_id_field").show();
      $("#loading").hide();
    });
  });


  $("#pull_id_field").change(function() {
    var val = $("#pull_id_field").val();
    $('#pull_details').empty();
    if (val == "" || gh_pulls.length === 0) {
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
      $('#pull_details').append('<b>'+pr.title+'</b><br>'+converter.makeHtml(pr.body)+'<p><a href="'+pr.html_url+'">View on GitHub</a></p>');
    }
  });

}); // document.load

</script>
<br>
<?php

$canpatch = false;
require "{$ROOT_DIR}/templates/listpulls.php";
response_footer();
