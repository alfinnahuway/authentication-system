<div class="container">

    <div class="card o-hidden border-0 shadow-lg my-5 col-lg-7 mx-auto">
        <div class="card-body p-0">
            <!-- Nested Row within Card Body -->
            <div class="row">
                <div class="col-lg">
                    <div class="p-5">
                        <div class="text-center">
                            <h1 class="h4 text-gray-900">Change your password</h1>
                            <h5 class="mb-4"><?= $this->session->userdata('reset_email'); ?></h5>
                        </div>
                        <form class="user" method="post" action="<?= base_url('auth/changepassword'); ?>">
                            <div class="form-group">
                                <input type="password" class="form-control form-control-user" id="password1" placeholder="Enter new password..." name="password1">
                                <?= form_error('password1', '<small class="text-danger pl-3">', '</small>'); ?>
                            </div>
                            <div class="form-group">
                                <input type="password" class="form-control form-control-user" id="password2" placeholder="Repeat Password..." name="password2">
                                <?= form_error('password2', '<small class="text-danger pl-3">', '</small>'); ?>
                            </div>

                            <button type="submit" class=" btn btn-primary btn-user btn-block">
                                Change Password
                            </button>
                        </form>
                        <hr>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>