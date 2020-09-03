<?php
/**
 * Created by PhpStorm.
 * User: hxc
 * Date: 2020/9/2
 * Time: 16:37
 */

class Tree{
    public $res='';
    public $left=null;
    public $right=null;
    public function __construct($res)
    {
        $this->res=$res;
    }

}

class BinarySortTree
{
    public $tree;

    public function getTree()
    {
        return $this->tree;
    }

    //插入
    public function insertTree($data)
    {
        if(!$this->tree){
            $this->tree=new Tree($data);
            return ;
        }
        $p=$this->tree;
        while($p){
            if($data<$p->res){  //如果插入结点当前结点
                if(!$p->left){  //并且不存在左子结点
                    $p->left=new Tree($data);
                    return ;
                }
                $p=$p->left;
            }elseif ($data>$p->res){
                if(!$p->right){
                    $p->right=new Tree($data);
                    return ;
                }
                $p=$p->right;
            }else{
                return ;
            }
        }
    }



    //删除
    public function deleteTree($data)
    {
        if (!$this->tree) {
            return;
        }
        $p = $this->tree;
        $fatherP = null;
        while ($p && $p->res !== $data) {
            $fatherP=$p; //结点的父结点
            if ($data > $p->res) {
                $p = $p->right;
            }else{
                $p=$p->left;
            }
        }

        //如果二叉树不存在
        if($p==null){
            var_dump('当前树中没有此结点');return;
        }

        //待删除待有两个子结点
        if($p->left && $p->right){
            $minR=$p->right;
            $minRR=$p;// 最小结点的父结点
            //查找右子树的最小结点
            while($minR->left){
                $minRR=$minR;
                $minR=$minR->left;
            }
            $p->res=$minR->res;//把右子树上最小结点的值赋值给待删除结点
            $p=$minR;
            $fatherP=$minRR;

        }
        $child=null;
        if($p->left){
            $child=$p->left;
        }elseif($p->right){
            $child=$p->right;
        }else{
            $child=null;
        }

        if(!$fatherP){ //待删除结点是根结点
            $this->tree=$child;
        }elseif ($fatherP->left==$p){ //待删除结点只有一个左结点，把待删除结点的父结点的left指向待删除结点的子节点
            $fatherP->left=$child;
        }else{                        //待删除结点只有一个右结点，把待删除结点的父结点的right指向待删除结点的子节点
            $fatherP->right=$child;
        }

    }
    //前序遍历节点
    public function front($tree)
    {
        if($tree == null) {
            return ;
        }
        echo $tree->res."\r\n";
        $this->front($tree->left);
        $this->front($tree->right);

    }
}

$sortTree=new BinarySortTree();
$sortTree->insertTree(9);
$sortTree->insertTree(8);
$sortTree->insertTree(10);
$sortTree->insertTree(5);
$sortTree->insertTree(6);
$sortTree->insertTree(4);
$sortTree->front($sortTree->tree);

