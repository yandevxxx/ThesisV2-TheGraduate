<?php // Sidebar role kaprodi (sisi "The Graduate") ?>

<!-- Sidebar -->

<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar" style="background-color: #1C476C;">

    <!-- Sidebar - Brand -->

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= base_url('graduate/prodi') ?>">

        <div class="sidebar-brand-icon">

            <img src="<?= base_url("assets/img/skripsi.png") ?>" alt="Logo" style="height: 3.5rem">

        </div>

        <div class="sidebar-brand-text mx-3">THE GRADUATE</div>

    </a>

    <!-- Divider -->

    <hr class="sidebar-divider">

    <div class="sidebar-heading">
        Program Studi
    </div>

    <li class="nav-item <?= (uri_string() === 'graduate/prodi') ? 'active' : '' ?>">
        <a class="nav-link py-2" href="<?= base_url("graduate/prodi") ?>">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dasbor</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <?php
    $tracerUri   = uri_string();
    $tracerItems = [
        [
            'menu'  => 'pertanyaan_tracer_studi',
            'path'  => 'graduate/pertanyaan_tracer_studi',
            'label' => 'Pertanyaan Tracer Study',
        ],
        [
            'menu'  => 'bank_pertanyaan_tracer_studi',
            'path'  => 'graduate/bank_pertanyaan_tracer_studi',
            'label' => 'Bank Pertanyaan',
        ],
        [
            'menu'  => 'kategori_tracer_studi',
            'path'  => 'graduate/kategori_tracer_studi',
            'label' => 'Kategori Pertanyaan',
        ],
        [
            'menu'  => 'tracer_studi_pantau',
            'path'  => 'graduate/tracer_studi_pantau',
            'label' => 'Pantau Tracer Study',
        ],
    ];
    $visibleTracerItems = array_values(array_filter(
        $tracerItems,
        static fn (array $item): bool => \App\Libraries\AccessControl::menuVisible($item['menu'], ['prodi'])
    ));
    $tracerOpen = false;
    foreach ($visibleTracerItems as $item) {
        if (str_starts_with($tracerUri, $item['path'])) {
            $tracerOpen = true;
            break;
        }
    }
    ?>
    <?php if ($visibleTracerItems !== []) : ?>
    <li class="nav-item <?= $tracerOpen ? 'active' : '' ?>">
        <a class="nav-link py-2 <?= $tracerOpen ? '' : 'collapsed' ?>" href="#" data-toggle="collapse" data-target="#collapseTracerStudy" aria-expanded="<?= $tracerOpen ? 'true' : 'false' ?>" aria-controls="collapseTracerStudy">
            <i class="fas fa-fw fa-clipboard-list"></i>
            <span>Tracer Study</span>
        </a>
        <div id="collapseTracerStudy" class="collapse <?= $tracerOpen ? 'show' : '' ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?php foreach ($visibleTracerItems as $item) : ?>
                <a class="collapse-item <?= str_starts_with($tracerUri, $item['path']) ? 'active' : '' ?>" href="<?= base_url($item['path']) ?>"><?= esc($item['label']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>

    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->
