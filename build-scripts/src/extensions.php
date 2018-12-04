<?php
function _install_build_deps( $dryRun ) {
    global $BUILTIN_EXTENSIONS, $PECL_EXTENSIONS;
    $extensions = array_merge($BUILTIN_EXTENSIONS, $PECL_EXTENSIONS);
    $buildDeps = array();
    foreach ( $extensions as $ext ) {
        $buildDeps = array_unique(array_merge( $buildDeps, $ext->getBuildDependencies(), $ext->getExtraDependencies() ));
    }
    if ( count( $buildDeps ) > 0 ) {
        run(array_merge(array('apt-get', 'install', '--no-install-recommends', '-y'), $buildDeps), $dryRun);
        run(array_merge(array('apt-mark', 'auto'), $buildDeps), $dryRun);
    }
}

function _mark_runtime_dependencies( $dryRun ) {
    global $BUILTIN_EXTENSIONS, $PECL_EXTENSIONS;
    $extensions = array_merge($BUILTIN_EXTENSIONS, $PECL_EXTENSIONS);
    $pkgs = array();
    foreach ( $extensions as $ext ) {
        $pkgs = array_merge( $pkgs, $ext->getExtraDependencies() );
    }

    $libs = array();
    run(array('find', PHP_EXTENSION_DIR,'-type', 'f', '-name', '*.so'), $dryRun, false, $libs);

    $libFiles = array();
    foreach ( $libs as $lib ) {
        run(array('ldd', $lib), $dryRun, false, $libDeps);
        foreach( $libDeps as $libDep ) {
            if ( strpos( $libDep, "=>" ) !== false ) {
                $libDep = preg_replace('/\s+/i', ' ', trim($libDep));
                $_l = explode( " ", $libDep );
                $libFiles[] = $_l[ count($_l) - 2 ]; 
            }
        }
    }
    $libFiles = array_unique($libFiles);

    foreach ( $libFiles as $libFile ) {
        $pkg = run(array('dpkg-query', '-S', $libFile), $dryRun, false);
        $pkg = preg_replace('/^([^:]+):.*$/s', '$1', $pkg);
        $pkgs[] = $pkg;
    }
    $pkgs = array_unique( $pkgs );

    run(array_merge(array('apt-mark', 'manual'), $pkgs), $dryRun);
}

function _clean_up( $dryRun ) {
    global $BUILTIN_EXTENSIONS, $PECL_EXTENSIONS;
    $extensions = array_merge($BUILTIN_EXTENSIONS, $PECL_EXTENSIONS);
    $buildDeps = array();
    foreach ( $extensions as $ext ) {
        $buildDeps = array_unique(array_merge( $buildDeps, $ext->getBuildDependencies() ));
    }
    if ( count( $buildDeps ) > 0 ) {
        run(array_merge(array('apt-get', 'autoremove', '-y', '--purge'), $buildDeps), $dryRun);
    }
}

function install_extensions( $dryRun ) {
    global $BUILTIN_EXTENSIONS, $PECL_EXTENSIONS;
    log_msg("Installing build time dependencies...");
    _install_build_deps( $dryRun );

    log_msg("Installing built-in extensions...");
    foreach ($BUILTIN_EXTENSIONS as $ext) {
        $ext->install( $dryRun );
    }

    log_msg("Installing pecl extensions...");
    foreach ($PECL_EXTENSIONS as $ext) {
        $ext->install( $dryRun );
    }

    log_msg("Marking runtime dependencies...");
    _mark_runtime_dependencies( $dryRun );

    log_msg("Cleaning up...");
    _clean_up( $dryRun );
}