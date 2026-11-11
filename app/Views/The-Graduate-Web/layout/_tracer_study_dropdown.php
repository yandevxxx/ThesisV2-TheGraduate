<?php
$tracerDropdownRoles = $tracerDropdownRoles
    ?? $sidebarRoles
    ?? (is_array(session()->get('user_session'))
        ? \App\Libraries\PortalRouter::normalizeRoles(session()->get('user_session'))
        : []);
$showPertanyaanTracer = \App\Libraries\AccessControl::menuVisible('pertanyaan_tracer_studi', $tracerDropdownRoles);
$showBankTracer       = \App\Libraries\AccessControl::menuVisible('bank_pertanyaan_tracer_studi', $tracerDropdownRoles);
$showPantauTracer     = \App\Libraries\AccessControl::menuVisible('tracer_studi_pantau', $tracerDropdownRoles);
?>
<?php if ($showPertanyaanTracer || $showBankTracer || $showPantauTracer) : ?>
    <?php
    $tracerUri          = uri_string();
    $isPertanyaanTracer = str_starts_with($tracerUri, 'graduate/pertanyaan_tracer_studi');
    $isBankTracer       = str_starts_with($tracerUri, 'graduate/bank_pertanyaan_tracer_studi');
    $isPantauTracer     = str_starts_with($tracerUri, 'graduate/tracer_studi_pantau');
    $tracerOpen         = ($isPertanyaanTracer || $isBankTracer || $isPantauTracer);
    ?>
    <li class="nav-item <?= $tracerOpen ? 'active' : '' ?>">
        <a class="nav-link py-2 <?= $tracerOpen ? '' : 'collapsed' ?>" href="#" data-toggle="collapse" data-target="#collapseTracerStudy" aria-expanded="<?= $tracerOpen ? 'true' : 'false' ?>" aria-controls="collapseTracerStudy">
            <i class="fas fa-fw fa-clipboard-list"></i>
            <span>Tracer Study</span>
        </a>
        <div id="collapseTracerStudy" class="collapse <?= $tracerOpen ? 'show' : '' ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?php if ($showPertanyaanTracer) : ?>
                <a class="collapse-item <?= $isPertanyaanTracer ? 'active' : '' ?>" href="<?= base_url('graduate/pertanyaan_tracer_studi') ?>">Pertanyaan Tracer Study</a>
                <?php endif; ?>
                <?php if ($showBankTracer) : ?>
                <a class="collapse-item <?= $isBankTracer ? 'active' : '' ?>" href="<?= base_url('graduate/bank_pertanyaan_tracer_studi') ?>">Bank Pertanyaan</a>
                <?php endif; ?>
                <?php if ($showPantauTracer) : ?>
                <a class="collapse-item <?= $isPantauTracer ? 'active' : '' ?>" href="<?= base_url('graduate/tracer_studi_pantau') ?>">Pantau Tracer Study</a>
                <?php endif; ?>
            </div>
        </div>
    </li>
<?php endif; ?>
