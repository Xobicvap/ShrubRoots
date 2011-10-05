<?php
/**
 *  ShrubRoots Dependency Injection Container
 *  Copyright (C) 2011 Rusty Hamilton (rusty@shrub3.net)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 * 
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Mock_ComplexFactory implements Roots_ICompileObjs, Roots_IFactoryBuild
{
    
    protected $obj_demap;
    protected $build_list;

    public function __construct(Roots_IDemappable $obj_demap) {
        $this->obj_demap = $obj_demap;
        
        $this->build_list = array('C' => array('A',
                                               'D' => array('A', 'B')));
    }

    public function buildPremapped() {
        $obj = $this->obj_demap->demap($this->build_list);
        return $obj;
    }

    public function build(array &$params) {
        $build_times = $params['build_times'];
        
        $i = 0;
        $obj_name = 'A';
        $blist = array();
        for ($i = 0; $i < $build_times; $i++) {
            $name = 'A_obj'.$i.'='.$obj_name;
            $blist[$i] = $name;
        }
        return $this->obj_demap->demap($blist);
    }

}
?>
