<?php

    require('../config.php');

    dol_include_once('/product/class/product.class.php');
    dol_include_once('/product/class/product.class.php');
    dol_include_once('/fourn/class/fournisseur.commande.class.php');
    dol_include_once("/fourn/class/fournisseur.class.php");
    dol_include_once("/commande/class/commande.class.php");
    dol_include_once('/product/stock/class/entrepot.class.php');
    dol_include_once('/expedition/class/expedition.class.php');

    $prod = new Product($db);
    $prod->fetch(GETPOST('idprod'));
    
    
    if($prod->id<=0)exit;
    
    $prod->load_stock();
    
    $r ='';
    foreach($prod->stock_warehouse as $fk_warehouse=>$obj) {
        
        $e=new Entrepot($db);
        $e->fetch($fk_warehouse);
        $r .='<br />'.$e->getNomUrl(1).' x '.$obj->real;
        
    }
    if(!empty($r)) {
            print '<p>';
            print '<strong>Stock physique</strong>';
            print $r;            
            print '</p>';
            
            
        }
    
    if(empty($conf->global->SOFO_USE_ONLY_OF_FOR_NEEDED_PRODUCT)) {
     $sql = "SELECT DISTINCT c.rowid, cd.qty";
        $sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd";
        $sql.= ", ".MAIN_DB_PREFIX."commande as c";
        $sql.= ", ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE c.rowid = cd.fk_commande";
        $sql.= " AND c.fk_soc = s.rowid";
        $sql.= " AND c.entity = ".$conf->entity;
        $sql.= " AND cd.fk_product = ".$prod->id;
        $sql.= " AND c.fk_statut in (1,2)";
        
        $r ='';
        $result =$db->query($sql);
        while($obj = $db->fetch_object($result)) {
            
            $c=new Commande($db);
            $c->fetch($obj->rowid);
            
            $r.='<br />'.$c->getNomUrl(1).' x '.$obj->qty.'';
            
        }
    
    if(!empty($r)) {
            print '<p>';
            print '<strong>Commande client</strong>';
            print $r;            
            print '</p>';
            
            
        }
    
    
    $sql = "SELECT cd.qty, c.rowid";
    $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd";
    $sql.= ", ".MAIN_DB_PREFIX."commande_fournisseur as c";
    $sql.= ", ".MAIN_DB_PREFIX."societe as s";
    $sql.= " WHERE c.rowid = cd.fk_commande";
    $sql.= " AND c.fk_soc = s.rowid";
    $sql.= " AND c.entity = ".$conf->entity;
    $sql.= " AND cd.fk_product = ".$prod->id;
    $sql.= " AND c.fk_statut in (3)"; 
    
    $r ='';
        $result =$db->query($sql);
        while($obj = $db->fetch_object($result)) {
            
            $c=new CommandeFournisseur($db);
            $c->fetch($obj->rowid);
            
            $r.='<br />'.$c->getNomUrl(1).' x '.$obj->qty.'';
            
        }
    
    if(!empty($r)) {
            print '<p>';
            print '<strong>Commande fournisseur</strong>';
            print $r;            
            print '</p>';
            
            
        }
    
        
        
    }
    
    
    
    
    
    if(!empty($conf->of->enabled)) {
        
        
        define('INC_FROM_DOLIBARR', true);
        dol_include_once('/of/config.php');
        dol_include_once('/of/class/ordre_fabrication_asset.class.php');
        $PDOdb=new TPDOdb;    
        $sql="SELECT ofe.rowid, ofe.numero, ofe.date_lancement , ofe.date_besoin, ofel.qty, ofel.qty_needed
        , ofe.status, ofe.fk_user, ofe.total_cost
          FROM ".MAIN_DB_PREFIX."assetOf as ofe 
          LEFT JOIN ".MAIN_DB_PREFIX."assetOf_line ofel ON (ofel.fk_assetOf=ofe.rowid AND ofel.type = 'TO_MAKE')
          WHERE ofe.entity=".$conf->entity." AND ofel.fk_product=".$prod->id." AND ofe.status IN ('VALID','OPEN') 
          ORDER BY date_besoin ASC"
          ;
        
        
        $PDOdb->Execute($sql);
        $res = '';
        while($obj = $PDOdb->Get_line()) {
            $res.= '<br /><a href="'.dol_buildpath('/of/fiche_of.php?id='.$obj->rowid,1).'">'.img_picto('','object_list.png','',0).' '.$obj->numero.'</a> x '.($obj->qty ? $obj->qty : $obj->qty_needed );    
        }
        
        if(!empty($res)) {
            print '<p>';
            print '<strong>Fabriqué par OF</strong>';
            print $res;            
            print '</p>';
            
            
        }
        
        $sql="SELECT ofe.rowid, ofe.numero, ofe.date_lancement , ofe.date_besoin, ofel.qty, ofel.qty_needed
        , ofe.status, ofe.fk_user, ofe.total_cost
          FROM ".MAIN_DB_PREFIX."assetOf as ofe 
          LEFT JOIN ".MAIN_DB_PREFIX."assetOf_line ofel ON (ofel.fk_assetOf=ofe.rowid AND ofel.type = 'NEEDED')
          WHERE ofe.entity=".$conf->entity." AND ofel.fk_product=".$prod->id." AND ofe.status IN ('VALID','OPEN') 
          ORDER BY date_besoin ASC"
          ;
        
        
        $PDOdb->Execute($sql);
        $res = '';
        while($obj = $PDOdb->Get_line()) {
            $res.= '<br /><a href="'.dol_buildpath('/of/fiche_of.php?id='.$obj->rowid,1).'">'.img_picto('','object_list.png','',0).' '.$obj->numero.'</a> x '.($obj->qty ? $obj->qty : $obj->qty_needed );    
        }
        
        if(!empty($res)) {
            print '<p>';
            print '<strong>Besoin dans OF</strong>';
            print $res;            
            print '</p>';
            
            
        }
    }

    $sql = "SELECT DISTINCT e.rowid, ed.qty";
        $sql.= " FROM ".MAIN_DB_PREFIX."expeditiondet as ed";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."expedition as e ON (e.rowid=ed.fk_expedition)";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commandedet as cd ON (ed.fk_origin_line=cd.rowid)";
        $sql.= " WHERE 1";
        $sql.= " AND e.entity = ".$conf->entity;
        $sql.= " AND cd.fk_product = ".$prod->id;
        $sql.= " AND e.fk_statut in (1)";
      
        $r ='';
        $result =$db->query($sql);
        //var_dump($db);
        while($obj = $db->fetch_object($result)) {
            
            $e=new Expedition($db);
            $e->fetch($obj->rowid);
            
            $r.='<br />'.$e->getNomUrl(1).' x '.$obj->qty.'';
            
        }
    
    
        if(!empty($r)) {
            print '<p>';
            print '<strong>Expéditions</strong>';
            print $r;            
            print '</p>';
            
            
        }
