<?php
    require_once('inc/session.php');
    $sql = include('inc/sql_connection.php');

    /* Check if user is logged in. If not, silently redirect them
        to the index page. */
    if (!isset($_SESSION['USER_ID'])) {
        header('Location: /');
        exit;
    }

    /* Attempt to select all questions sets owned/created by the current user and count
        the total number of questions within each set. Sets without any questions will default to 0.
        If no questions sets are found, then the total rowCount will be 0. To remove duplicate rows, we group each
        row by label and then order it by the ID number. */
    $query = $sql->prepare('SELECT qs.id, qs.label, COUNT(q.id) AS total_questions FROM question_sets qs LEFT JOIN questions q ON (qs.id = q.set_id) WHERE qs.user_id = ? GROUP BY qs.label ORDER BY qs.id');
    $query->execute(array(
        $_SESSION['USER_ID']
    ));

    /* Save the data from our search results. This will be used later in the page. */
    $rows = $query->fetchAll(PDO::FETCH_ASSOC);
    $total_questions = $query->rowCount();
    $count = 1; //Relative question set number
?>
<!DOCTYPE html>
<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"><![endif]-->
<!--[if IE 7]><html class="no-js lt-ie9 lt-ie8" lang="en"><![endif]-->
<!--[if IE 8]><html class="no-js lt-ie9" lang="en"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="en"><!--<![endif]-->
    <head>
        <?php include 'inc/meta.php'; ?>
        <title>User Panel :: CSU Clicker</title>

        <?php include 'inc/header.php'; ?>
        <style>
            body {
                padding-top: 60px;
            }
        </style>
    </head>
    <body>
        <header>
            <nav class="navbar navbar-primary navbar-fixed-top text-primary light">
                <div class="container-fluid">
                    <div class="navbar-header">
                        <button class="navbar-toggle collapsed" type="button" data-toggle="collapse" data-target=".navbar-collapse">
                            <i class="material-icons">menu</i>
                        </button>
                        <a class="navbar-brand" href="/">
                            <img class="img-responsive" src="img/logo.png" alt="CSU Clicker">
                        </a> <!-- /.navbar-brand -->
                    </div> <!-- /.navbar-header -->

                    <div class="collapse navbar-collapse">
                        <ul class="nav navbar-nav navbar-right">
                            <li class="active"><a href="/panel">Panel</a></li>
                            <li><a href="/logout">Logout</a></li>
                        </ul> <!-- /.navbar-nav -->
                    </div> <!-- /.navbar-collapse -->
                </div> <!-- /.container -->
            </nav> <!-- /nav -->
        </header> <!-- /header -->

        <main class="container">
            <h1>User Panel</h1>
            <div class="row clearfix">
                <div class="col-md-4">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-header text-primary light">Account</h3>
                        </div> <!-- /.panel-heading -->
                        
                        <div class="panel-body">
                            <p>
                                Hi, <b><?php echo htmlspecialchars($_SESSION['USER_FIRST_NAME']); ?></b>!<br>
                                Welcome to your personal control panel. From here you can create, edit, and delete any questions sets you have.
                                <br><br>
                                <b>CSU ID</b>: <?php echo htmlspecialchars(substr_replace($_SESSION['USER_ID'], '***', 0, 3)); ?><br>
                                <b>Total Question Sets</b>: <?php echo $total_questions; ?>
                            </p>
                            <hr>
                            <p>
                                <a href="/change">Change Password</a><br>
                                <a href="/contact">Need help?</a>
                            </p>
                        </div>
                    </div> <!-- end panel -->
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-header text-primary light">Join Session</h3>
                        </div> <!-- /.panel-heading -->
                        
                        <div class="panel-body">
                            <p>Enter in the unique 8-character session token to join a session (ie: <code>4ad7fefa</code>)</p>
                            <form id="form-session" class="form-vertical" action="/join.php" method="post" validate>
                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group no-margin">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">group_work</i></span>
                                                <input id="session_token" class="form-control" type="text" name="session_token" title="Unique 8-character session token" pattern="^[a-fA-F0-9]{8}$" placeholder="Session ID" required>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <div class="text-right">
                                            <button id="join-session" class="btn btn-raised btn-accent" type="submit">Join</button>
                                        </div> <!-- /.text-right -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->
                            </form> <!-- /#form-login -->
                        </div>
                    </div> <!-- end panel -->
                </div> <!-- end col-md-4 -->

                <div class="col-md-8">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-header text-primary light">Question Sets</h3>
                        </div> <!-- /.panel-heading -->
                        
                        <div class="panel-body">
                            <?php if ($total_questions == 0) : ?>
                            <h3 class="text-center no-margin">
                                You have no question sets!<br>
                                <small>Click the <i class="material-icons">create</i> icon to make one</small>
                            </h3>
                            <?php else : ?>
                            <p>Below are all the questions sets you've created. Highlighted sets indicate that there is a problem with the set (ie: no questions, invalid answers, etc.)</p>
                            <table id="question-sets" class="table table-striped table-hover">
                                <thead>
                                    <col width="32">
                                    <col width="9999">
                                    <tr>
                                        <th>#</th>
                                        <th>Label</th>
                                        <th class="text-center">Questions</th>
                                        <th class="text-center"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $set) : ?>
                                    <tr id="<?php echo $set['id']; ?>" class="<?php echo $set['total_questions'] == 0 ? 'warning' : ''; ?>">
                                        <?php
                                            echo '<td>' . $count++ . '</td>';
                                            echo '<td>' . $set['label'] . '</td>';
                                            echo '<td class="text-center">' . $set['total_questions'] . '</td>';
                                        ?>
                                        <td class="text-center"><a href="/view?id=<?php echo $set['id']; ?>" class="btn btn-sm btn-raised btn-accent">View</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div> <!-- end panel -->
                </div> <!-- end col-md-8 -->
            </div> <!-- end row -->
            
            <!-- Floating Action Button -->
            <div class="fab">
                <span data-toggle="tooltip" data-placement="left" title="" data-original-title="Create">
                    <a href="javascript:void(0);" class="btn btn-fab btn-accent" data-toggle="modal" data-target="#modal-qs-create" role="button"><i class="material-icons">create</i></a>
                </span>
            </div> <!-- /.fab -->
        
            <!-- Create Question Set Modal -->
            <div id="modal-qs-create" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-body">
                            <h2>Create Question Set</h2>
                            <h3 class="text-light text-center">
                                Give your question set a label<br>
                                <small>(You can edit this later)</small>
                            </h3>
                            
                            <form id="form-qs-create" class="form-vertical" action="/qs_create.php" method="post" validate>
                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <fieldset class="form-group">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="material-icons">label</i></span>
                                                <input id="label" class="form-control" type="text" name="label" placeholder="Label" required>
                                            </div> <!-- /.input-group -->
                                        </fieldset> <!-- /.form-group -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->

                                <div class="row clearfix">
                                    <div class="col-xs-12">
                                        <div class="text-right">
                                            <button id="submit-qs-create" class="btn btn-raised btn-accent" type="submit">Create</button>
                                            <button id="cancel-qs-create" class="btn btn-default" type="reset" data-dismiss="modal">Cancel</button>
                                        </div> <!-- /.text-right -->
                                    </div> <!-- /.col -->
                                </div> <!-- /.row -->
                            </form> <!-- /#form-qs-create -->
                        </div> <!-- /.modal-body -->
                    </div> <!-- /.modal-content -->
                </div> <!-- /.modal-dialog -->
            </div> <!-- /.modal -->
        </main> <!-- end container -->

        <?php include 'inc/footer.php'; ?>
        <?php include 'inc/notice.php'; ?>
        <?php include 'inc/error.php'; ?>
    </body>
</html>
