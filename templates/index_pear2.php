<h1>PEAR2 Bug Tracking System</h1>

<div class="col">
    <h3>Report a new Bug</h3>
    <p>
        Got a test case?<br />
        Reproducible steps?
    </p>
    Choose the package to report a bug for:
    <form action="report.php" method="get">
        <select name="package">
            <?php
                $package_list = $webfrontend->listPackages();
                foreach ($package_list->packages as $package) {
                    echo '<option>'.$package.'</option>';
                }
            ?>
        </select>
        <input type="submit" value="Report Bug" />
    </form>
</div>
<div class="col">
    <h3>Get help</h3>
    Try:
    <ul>
        <li>Mailing lists</li>
        <li><a href="irc://irc.efnet.org/pear">IRC</a></li>
        <li>Documentation</li>
    </ul>
</div>
<div class="col">
    <h3>Suggest an idea</h3>
    <form action="report.php" method="get">
        <select name="package">
            <?php
                reset($package_list);
                foreach ($package_list->packages as $package) {
                    echo '<option>'.$package.'</option>';
                }
            ?>
        </select>
        <input type="submit" value="Request Feature" />
    </form>
</div>

